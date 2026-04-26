<?php

declare(strict_types=1);

namespace App\Domain\Ai\Entities;

use App\Domain\Ai\Events\EmbeddingGenerated;
use App\Domain\Shared\Entities\AggregateRoot;

final class KnowledgeChunk extends AggregateRoot
{
    /**
     * @param list<float> $embedding
     */
    private function __construct(
        public readonly string $id,
        public readonly string $sourceUrl,
        public readonly string $content,
        public readonly array $embedding,
    ) {}

    /**
     * @param list<float> $embedding
     */
    public static function generate(string $id, string $sourceUrl, string $content, array $embedding): self
    {
        $chunk = new self($id, $sourceUrl, $content, $embedding);
        $chunk->recordEvent(new EmbeddingGenerated($id, $sourceUrl, count($embedding)));

        return $chunk;
    }

    /**
     * @param list<float> $embedding
     */
    public static function rehydrate(string $id, string $sourceUrl, string $content, array $embedding): self
    {
        return new self($id, $sourceUrl, $content, $embedding);
    }
}
