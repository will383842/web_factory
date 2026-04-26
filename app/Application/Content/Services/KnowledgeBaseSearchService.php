<?php

declare(strict_types=1);

namespace App\Application\Content\Services;

/**
 * Port for KB search.
 *
 * Sprint-7 default impl lives in App\Infrastructure\Content (PgVector
 * adapter) — avoid the direct `use` import here, Application must not
 * depend on Infrastructure (ADR 0008 hexagonal ports & adapters).
 */
interface KnowledgeBaseSearchService
{
    /**
     * @return list<KnowledgeChunkSearchResult>
     */
    public function search(string $projectId, string $query, int $limit = 5): array;
}
