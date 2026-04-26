<?php

declare(strict_types=1);

namespace App\Application\Content\Services;

/**
 * Port for vector-embedding providers.
 *
 * Sprint-7 default impl is deterministic (hash-based bag-of-words) so the
 * pipeline + KB search are testable without any external API. Sprint 19
 * will swap in OpenAI's `text-embedding-3-small` (1536-dim) — the
 * `vector(384)` column will be widened in that sprint.
 */
interface EmbeddingService
{
    /**
     * @return list<float> fixed-length embedding (Sprint 7 = 384 dims)
     */
    public function embed(string $text): array;

    public function dimensions(): int;
}
