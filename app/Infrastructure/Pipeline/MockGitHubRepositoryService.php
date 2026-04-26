<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\GitHubRepoInfo;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-6 deterministic stand-in for the real GitHub adapter.
 *
 * Returns a `webfactory-org/<slug>` repository identity without any network
 * call. Sprint 16 will swap to an Octokit-style adapter using a PAT
 * (the GH_TOKEN secret) — see Spec 09 (Deployment) for details.
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
}
