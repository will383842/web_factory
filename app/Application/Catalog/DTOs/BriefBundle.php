<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 4 — the 35-file project brief that feeds the
 * code-generation pass (step 5). Each entry maps a relative path inside
 * the brief archive to its file content.
 *
 *   "README.md"               => "# My SaaS\n\nDescription..."
 *   "blueprint.json"          => "{...}"
 *   "design/tokens.json"      => "{...}"
 *   "pages/home.md"           => "# Home page brief..."
 *   "mockups/home-hero.html"  => "<section>...</section>"
 *   ...
 *
 * The brief is uploaded to MinIO and referenced by the GitHub repo as
 * `webfactory.brief.zip`.
 */
final readonly class BriefBundle
{
    /**
     * @param array<string, string> $files relative-path => content map
     */
    public function __construct(
        public array $files,
        public string $checksum,
    ) {}

    public function fileCount(): int
    {
        return count($this->files);
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'file_count' => $this->fileCount(),
            'checksum' => $this->checksum,
            'files' => array_keys($this->files),
        ];
    }
}
