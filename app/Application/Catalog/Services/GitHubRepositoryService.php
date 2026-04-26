<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\GitHubRepoInfo;
use App\Domain\Catalog\Entities\Project;

/**
 * Pipeline step 5 port — creates the GitHub repository for the project.
 *
 * Sprint 6 default impl is a deterministic mock; Sprint 16 will swap in
 * an Octokit-style adapter using a personal access token.
 */
interface GitHubRepositoryService
{
    public function createRepository(Project $project): GitHubRepoInfo;
}
