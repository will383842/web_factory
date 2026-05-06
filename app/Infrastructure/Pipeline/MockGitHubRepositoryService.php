<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\GitHubCommitInfo;
use App\Application\Catalog\DTOs\GitHubRepoInfo;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Domain\Catalog\Entities\Project;
use Illuminate\Support\Facades\Storage;

/**
 * Sprint-6 deterministic stand-in for the real GitHub adapter.
 *
 * Returns a `webfactory-org/<slug>` repository identity without any network
 * call. Sprint 16 will swap to an Octokit-style adapter using a PAT
 * (the GH_TOKEN secret) — see Spec 09 (Deployment) for details.
 *
 * `commitFile()` writes to the configured filesystem disk under
 * `repos/<full_name>/<path>` so it can be audited by tests and inspected
 * via MinIO in dev.
 */
final class MockGitHubRepositoryService implements GitHubRepositoryService
{
    private const ORG = 'webfactory-org';

    public function createRepository(Project $project): GitHubRepoInfo
    {
        $slug = $project->slug->value;

        return new GitHubRepoInfo(
            fullName: self::ORG.'/'.$slug,
            htmlUrl: 'https://github.com/'.self::ORG.'/'.$slug,
            sshUrl: 'git@github.com:'.self::ORG.'/'.$slug.'.git',
            defaultBranch: 'main',
        );
    }

    public function commitFile(
        GitHubRepoInfo $repo,
        string $path,
        string $content,
        string $commitMessage,
    ): GitHubCommitInfo {
        $storagePath = 'repos/'.$repo->fullName.'/'.ltrim($path, '/');

        // Always write to the `local` disk: this mock is a stand-in for an
        // outbound HTTP API call. The real Sprint-16 Octokit adapter will not
        // touch Storage at all — it will PUT to api.github.com — so we don't
        // need to honor the project-wide default disk here.
        Storage::disk('local')->put($storagePath, $content);

        // Deterministic synthetic SHA — sha1 of (full_name + path + content)
        // is stable across re-runs with identical inputs (idempotent for tests).
        $sha = sha1($repo->fullName.'|'.$path.'|'.$content);

        return new GitHubCommitInfo(
            sha: $sha,
            path: $path,
            htmlUrl: $repo->htmlUrl.'/blob/'.$repo->defaultBranch.'/'.$path,
            message: $commitMessage,
        );
    }
}
