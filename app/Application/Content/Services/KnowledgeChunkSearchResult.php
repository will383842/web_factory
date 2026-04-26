<?php

declare(strict_types=1);

namespace App\Application\Content\Services;

/**
 * Result row from a {@see KnowledgeBaseSearchService} cosine-similarity search.
 */
final readonly class KnowledgeChunkSearchResult
{
    public function __construct(
        public int $chunkId,
        public string $projectId,
        public string $sourceType,
        public ?int $sourceId,
        public string $content,
        public float $similarity,
    ) {}
}
