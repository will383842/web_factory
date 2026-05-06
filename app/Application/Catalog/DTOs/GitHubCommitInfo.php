<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Result of a single-file commit pushed to a project's GitHub repository.
 *
 * Sprint 16 wires this to a real Octokit-style adapter using a PAT — the
 * Sprint-6 mock writes to a local filesystem disk under `repos/<full_name>/`
 * and returns a synthetic SHA so callers can audit it in metadata.
 */
final readonly class GitHubCommitInfo
{
    public function __construct(
        public string $sha,
        public string $path,
        public string $htmlUrl,
        public string $message,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toMetadataArray(): array
    {
        return [
            'sha' => $this->sha,
            'path' => $this->path,
            'html_url' => $this->htmlUrl,
            'message' => $this->message,
        ];
    }
}
