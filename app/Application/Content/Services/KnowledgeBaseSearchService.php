<?php

declare(strict_types=1);

namespace App\Application\Content\Services;

use App\Infrastructure\Content\PgVectorKnowledgeBase;

/**
 * Port for KB search.
 *
 * Sprint-7 default impl: {@see PgVectorKnowledgeBase}
 * which uses pgvector cosine distance under the hood.
 */
interface KnowledgeBaseSearchService
{
    /**
     * @return list<KnowledgeChunkSearchResult>
     */
    public function search(string $projectId, string $query, int $limit = 5): array;
}
