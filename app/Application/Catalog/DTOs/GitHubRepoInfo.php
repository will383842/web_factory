<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 5 — coordinates of the GitHub repository created
 * for the project. Sprint 6 uses a deterministic mock; Sprint 16 will swap
 * in the real Octokit-style adapter (Spec 09 — Deployment).
 */
final readonly class GitHubRepoInfo
{
    public function __construct(
        public string $fullName,    // org/slug
        public string $htmlUrl,
        public string $sshUrl,
        public string $defaultBranch,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toMetadataArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'html_url' => $this->htmlUrl,
            'ssh_url' => $this->sshUrl,
            'default_branch' => $this->defaultBranch,
        ];
    }
}
