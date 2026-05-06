<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\GitPushResult;
use Symfony\Component\Process\Process;

/**
 * Sprint 29 — runs `git init / add / commit / remote add / push` inside
 * the wf-app container against a host-mounted workspace.
 *
 * Auth: if a PAT is configured, it is injected into the HTTPS URL only
 * for the `git push` invocation; the remote URL is reset to the clean
 * form afterwards so the token never persists on disk.
 *
 * Idempotency: re-running on an already-pushed repo is safe — `git init`
 * is a no-op on an existing repo, `git remote add` is replaced by
 * `set-url` if origin already points elsewhere, and `git commit` skips
 * silently if the working tree is clean.
 */
final class WorkspaceGitPusher
{
    public function __construct(
        private readonly ?string $personalAccessToken,
        private readonly string $userName,
        private readonly string $userEmail,
        private readonly string $defaultBranch,
        private readonly string $commitMessage,
    ) {}

    public function push(string $workspacePath, string $githubHttpsUrl, string $hostPath): GitPushResult
    {
        $phases = [
            'init' => false,
            'configure' => false,
            'add' => false,
            'commit' => false,
            'remote' => false,
            'push' => false,
        ];
        $output = '';
        $error = null;
        $commitSha = null;
        $manualCommand = null;

        if (! is_dir($workspacePath)) {
            return new GitPushResult(
                success: false,
                remoteUrl: $githubHttpsUrl,
                commitSha: null,
                manualCommand: null,
                output: '',
                errorMessage: "Workspace not found: {$workspacePath}",
                phases: $phases,
            );
        }

        $cleanUrl = $this->normalizeRemoteUrl($githubHttpsUrl);

        // 1. init (idempotent — already-initialized repo is OK).
        [$ok, $stdout] = $this->run(['git', 'init', '-b', $this->defaultBranch], $workspacePath);
        $output .= $stdout;
        $phases['init'] = $ok;
        if (! $ok) {
            return $this->failure($phases, $cleanUrl, $output, "git init failed.\n{$stdout}");
        }

        // 2. configure user (local to repo, doesn't touch global gitconfig)
        // and disable commit signing to avoid GPG/PGP prompts.
        foreach ([
            ['git', 'config', '--local', 'user.name', $this->userName],
            ['git', 'config', '--local', 'user.email', $this->userEmail],
            ['git', 'config', '--local', 'commit.gpgsign', 'false'],
            ['git', 'config', '--local', 'tag.gpgsign', 'false'],
            ['git', 'config', '--local', '--add', 'safe.directory', $workspacePath],
        ] as $cmd) {
            [$ok, $stdout] = $this->run($cmd, $workspacePath);
            $output .= $stdout;
            if (! $ok) {
                return $this->failure($phases, $cleanUrl, $output, "git config failed: {$stdout}");
            }
        }
        $phases['configure'] = true;

        // 3. add everything respecting .gitignore.
        [$ok, $stdout] = $this->run(['git', 'add', '-A'], $workspacePath);
        $output .= $stdout;
        $phases['add'] = $ok;
        if (! $ok) {
            return $this->failure($phases, $cleanUrl, $output, "git add failed: {$stdout}");
        }

        // 4. commit — `git diff --cached --quiet` returns 1 if there are
        //    staged changes, 0 if the index is clean. We commit only if dirty.
        [$cleanIndex] = $this->run(['git', 'diff', '--cached', '--quiet'], $workspacePath);
        if (! $cleanIndex) {
            [$ok, $stdout] = $this->run(['git', 'commit', '-m', $this->commitMessage], $workspacePath);
            $output .= $stdout;
            $phases['commit'] = $ok;
            if (! $ok) {
                return $this->failure($phases, $cleanUrl, $output, "git commit failed: {$stdout}");
            }
        } else {
            // Index empty (already committed before, or working tree clean) — nothing to do, but consider success.
            $phases['commit'] = true;
        }

        // Capture HEAD sha (best-effort).
        [$shaOk, $shaOut] = $this->run(['git', 'rev-parse', 'HEAD'], $workspacePath);
        if ($shaOk) {
            $commitSha = trim($shaOut);
        }

        // 5. remote add (or set-url if exists).
        [$hasRemote] = $this->run(['git', 'remote', 'get-url', 'origin'], $workspacePath);
        $remoteCmd = $hasRemote
            ? ['git', 'remote', 'set-url', 'origin', $cleanUrl]
            : ['git', 'remote', 'add', 'origin', $cleanUrl];
        [$ok, $stdout] = $this->run($remoteCmd, $workspacePath);
        $output .= $stdout;
        $phases['remote'] = $ok;
        if (! $ok) {
            return $this->failure($phases, $cleanUrl, $output, "git remote failed: {$stdout}");
        }

        // 6. push — try only if a PAT is configured. Without it, the
        //    container has no way to authenticate, so we surface the
        //    manual command for the developer to run on their host.
        if ($this->personalAccessToken === null || $this->personalAccessToken === '') {
            $manualCommand = sprintf('cd %s && git push -u origin %s', $hostPath, $this->defaultBranch);

            return new GitPushResult(
                success: false,
                remoteUrl: $cleanUrl,
                commitSha: $commitSha,
                manualCommand: $manualCommand,
                output: $output,
                errorMessage: 'No WEBFACTORY_GH_TOKEN configured — repo prepared, run the manual command from your terminal to finish.',
                phases: $phases,
            );
        }

        $authedUrl = $this->withTokenInUrl($cleanUrl, $this->personalAccessToken);

        [$ok, $stdout] = $this->run(['git', 'push', '-u', $authedUrl, $this->defaultBranch], $workspacePath);
        $output .= $stdout;
        $phases['push'] = $ok;
        if (! $ok) {
            // Reset URL just in case it leaked into git config (it should not — we passed URL directly to push).
            $this->run(['git', 'remote', 'set-url', 'origin', $cleanUrl], $workspacePath);
            $manualCommand = sprintf('cd %s && git push -u origin %s', $hostPath, $this->defaultBranch);

            return $this->failure($phases, $cleanUrl, $output, "git push failed: {$stdout}", $manualCommand, $commitSha);
        }

        return new GitPushResult(
            success: true,
            remoteUrl: $cleanUrl,
            commitSha: $commitSha,
            manualCommand: null,
            output: $output,
            errorMessage: null,
            phases: $phases,
        );
    }

    /**
     * Strip a token if the URL was passed pre-augmented, normalize trailing slashes,
     * and ensure the URL is HTTPS form (refuses SSH for token-based auth).
     */
    private function normalizeRemoteUrl(string $url): string
    {
        $url = trim($url);
        // Strip an embedded user:pass for safety (we re-inject our own).
        $url = (string) preg_replace('#^https://[^@/]+@#i', 'https://', $url);
        // Add `.git` suffix if missing — GitHub accepts both but `.git` is canonical.
        if (str_starts_with($url, 'https://') && ! str_ends_with($url, '.git')) {
            $url .= '.git';
        }

        return $url;
    }

    private function withTokenInUrl(string $cleanUrl, string $token): string
    {
        if (! str_starts_with($cleanUrl, 'https://')) {
            return $cleanUrl;
        }

        return 'https://x-access-token:'.urlencode($token).'@'.substr($cleanUrl, strlen('https://'));
    }

    /**
     * @param list<string> $command
     *
     * @return array{0: bool, 1: string}
     */
    private function run(array $command, string $cwd): array
    {
        $process = new Process($command, $cwd);
        $process->setTimeout(120);
        $process->run();

        $output = $process->getOutput()."\n".$process->getErrorOutput();

        return [$process->isSuccessful(), $output];
    }

    /**
     * @param array<string, bool> $phases
     */
    private function failure(
        array $phases,
        string $remoteUrl,
        string $output,
        string $error,
        ?string $manualCommand = null,
        ?string $commitSha = null,
    ): GitPushResult {
        return new GitPushResult(
            success: false,
            remoteUrl: $remoteUrl,
            commitSha: $commitSha,
            manualCommand: $manualCommand,
            output: $output,
            errorMessage: $error,
            phases: $phases,
        );
    }
}
