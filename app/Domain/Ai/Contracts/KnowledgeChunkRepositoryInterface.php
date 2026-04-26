<?php

declare(strict_types=1);

namespace App\Domain\Ai\Contracts;

use App\Domain\Ai\Entities\KnowledgeChunk;

interface KnowledgeChunkRepositoryInterface
{
    public function findById(string $id): ?KnowledgeChunk;

    public function save(KnowledgeChunk $chunk): void;

    /**
     * @param list<float> $queryEmbedding
     *
     * @return list<KnowledgeChunk>
     */
    public function searchByVector(array $queryEmbedding, int $limit = 5): array;
}
