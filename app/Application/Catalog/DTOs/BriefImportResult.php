<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

use App\Application\Catalog\Services\BriefImporter;

/**
 * Outcome of {@see BriefImporter::import()}.
 *
 * The brief ZIP has been extracted to `workspaceContainerPath` (visible to
 * wf-app) which is the same directory on disk as `workspaceHostPath` (the
 * path the developer types into their terminal to `cd` into).
 *
 * Stored verbatim under `Project.metadata.brief` so the admin UI can render
 * the host path without having to recompute it.
 */
final readonly class BriefImportResult
{
    /**
     * @param array<string, mixed> $parsedClaudeMd
     */
    public function __construct(
        public string $workspaceHostPath,
        public string $workspaceContainerPath,
        public ?string $claudeMdRelativePath,
        public int $extractedFileCount,
        public int $docsFileCount,
        public int $totalBytes,
        public array $parsedClaudeMd,
    ) {}

    /**
     * Shape persisted under `Project.metadata.brief`.
     *
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'workspace_host_path' => $this->workspaceHostPath,
            'workspace_container_path' => $this->workspaceContainerPath,
            'claude_md_relative_path' => $this->claudeMdRelativePath,
            'extracted_file_count' => $this->extractedFileCount,
            'docs_file_count' => $this->docsFileCount,
            'total_bytes' => $this->totalBytes,
            'parsed_claude_md' => $this->parsedClaudeMd,
            'imported_at' => now()->toIso8601String(),
        ];
    }
}
