<?php

declare(strict_types=1);

use App\Application\Catalog\Services\WorkspaceGitPusher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

/**
 * Build a fresh ephemeral workspace + a bare-repo "remote" so we can run
 * the real pusher end-to-end without ever talking to GitHub.
 *
 * @return array{0: WorkspaceGitPusher, 1: string, 2: string}
 *                                                            [pusher, workspacePath, fakeRemoteUrl]
 */
function makeGitFixtures(?string $pat = null): array
{
    $base = sys_get_temp_dir().'/wf-git-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($base);

    // Ephemeral source workspace with one tracked file.
    $workspace = $base.'/workspace';
    File::ensureDirectoryExists($workspace);
    File::put($workspace.'/README.md', "# Test\n\nGenerated.");
    File::put($workspace.'/.gitignore', "/.tmp\n");

    // Bare repo acting as the GitHub remote.
    $remote = $base.'/fake-remote.git';
    (new Process(['git', 'init', '--bare', $remote]))->mustRun();

    $pusher = new WorkspaceGitPusher(
        personalAccessToken: $pat,
        userName: 'Test User',
        userEmail: 'test@example.com',
        defaultBranch: 'main',
        commitMessage: 'Test commit',
    );

    return [$pusher, $workspace, $remote];
}

it('runs init / configure / add / commit / remote against a fresh workspace', function (): void {
    [$pusher, $workspace, $remote] = makeGitFixtures();

    // No PAT → push is skipped and a manual_command is surfaced.
    $result = $pusher->push($workspace, $remote, hostPath: 'C:/Users/willi/Documents/Projets/test');

    expect($result->phases)->toHaveKey('init')
        ->and($result->phases['init'])->toBeTrue()
        ->and($result->phases['configure'])->toBeTrue()
        ->and($result->phases['add'])->toBeTrue()
        ->and($result->phases['commit'])->toBeTrue()
        ->and($result->phases['remote'])->toBeTrue()
        ->and($result->phases['push'])->toBeFalse()
        ->and($result->success)->toBeFalse()
        ->and($result->manualCommand)->toContain('git push -u origin main')
        ->and($result->manualCommand)->toContain('C:/Users/willi/Documents/Projets/test')
        ->and($result->commitSha)->toHaveLength(40);

    // Verify the workspace is now an actual git repo with origin set.
    $proc = new Process(['git', 'remote', 'get-url', 'origin'], $workspace);
    $proc->mustRun();
    expect(trim($proc->getOutput()))->toContain('fake-remote.git');

    File::deleteDirectory(dirname($workspace));
});

it('returns a failure when the workspace does not exist', function (): void {
    $pusher = new WorkspaceGitPusher(
        personalAccessToken: null,
        userName: 'x',
        userEmail: 'x@x',
        defaultBranch: 'main',
        commitMessage: 'x',
    );

    $result = $pusher->push('/no/such/path/xyz', 'https://github.com/x/y.git', '/host/x');

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('Workspace not found');
});

it('is idempotent: re-running on an already-committed workspace does not re-commit', function (): void {
    [$pusher, $workspace, $remote] = makeGitFixtures();

    $first = $pusher->push($workspace, $remote, hostPath: '/host/x');
    expect($first->phases['commit'])->toBeTrue();
    $firstSha = $first->commitSha;

    // Second run with no changes — commit phase must succeed (clean index path).
    $second = $pusher->push($workspace, $remote, hostPath: '/host/x');
    expect($second->phases['commit'])->toBeTrue()
        ->and($second->commitSha)->toBe($firstSha);

    File::deleteDirectory(dirname($workspace));
});

it('updates the remote URL via set-url when origin already exists with a different value', function (): void {
    [$pusher, $workspace, $remote] = makeGitFixtures();

    // First push → adds origin
    $pusher->push($workspace, $remote, hostPath: '/host/x');

    // Now pretend we changed our mind and use a different remote.
    $newRemote = sys_get_temp_dir().'/wf-other-'.bin2hex(random_bytes(4)).'.git';
    (new Process(['git', 'init', '--bare', $newRemote]))->mustRun();

    $result = $pusher->push($workspace, $newRemote, hostPath: '/host/x');

    expect($result->phases['remote'])->toBeTrue();

    $proc = new Process(['git', 'remote', 'get-url', 'origin'], $workspace);
    $proc->mustRun();
    expect(trim($proc->getOutput()))->toContain(basename($newRemote));

    File::deleteDirectory(dirname($workspace));
    File::deleteDirectory($newRemote);
});

it('normalizes the remote URL by appending .git when missing and stripping embedded credentials', function (): void {
    $pusher = new WorkspaceGitPusher(
        personalAccessToken: null,
        userName: 'x',
        userEmail: 'x@x',
        defaultBranch: 'main',
        commitMessage: 'x',
    );

    $reflection = new ReflectionClass($pusher);
    $method = $reflection->getMethod('normalizeRemoteUrl');
    $method->setAccessible(true);

    expect($method->invoke($pusher, 'https://github.com/owner/repo'))
        ->toBe('https://github.com/owner/repo.git')
        ->and($method->invoke($pusher, 'https://oldtoken@github.com/owner/repo.git'))
        ->toBe('https://github.com/owner/repo.git')
        ->and($method->invoke($pusher, 'https://github.com/owner/repo.git'))
        ->toBe('https://github.com/owner/repo.git');
});

it('injects the PAT into the URL only for the push command (not in stored config)', function (): void {
    $pusher = new WorkspaceGitPusher(
        personalAccessToken: 'ghp_secret_token_xyz',
        userName: 'x',
        userEmail: 'x@x',
        defaultBranch: 'main',
        commitMessage: 'x',
    );

    $reflection = new ReflectionClass($pusher);
    $method = $reflection->getMethod('withTokenInUrl');
    $method->setAccessible(true);

    $augmented = $method->invoke($pusher, 'https://github.com/owner/repo.git', 'ghp_secret_token_xyz');
    expect($augmented)->toBe('https://x-access-token:ghp_secret_token_xyz@github.com/owner/repo.git');

    $unchanged = $method->invoke($pusher, 'git@github.com:owner/repo.git', 'ghp');
    expect($unchanged)->toBe('git@github.com:owner/repo.git');
});

it('binds WorkspaceGitPusher as a singleton via DomainServiceProvider', function (): void {
    $a = app(WorkspaceGitPusher::class);
    $b = app(WorkspaceGitPusher::class);
    expect($a)->toBeInstanceOf(WorkspaceGitPusher::class)
        ->and($a)->toBe($b);
});
