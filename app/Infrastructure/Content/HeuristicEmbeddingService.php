<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Application\Content\Services\EmbeddingService;

/**
 * Sprint-7 deterministic placeholder for the embedding port.
 *
 * Hash-based bag-of-words → 384-dim float vector, L2-normalized so cosine
 * similarity behaves correctly. Two semantically similar inputs (sharing
 * many tokens) produce close vectors; unrelated inputs produce orthogonal
 * vectors. Tests assert that property.
 *
 * Sprint 19 will swap to OpenAI text-embedding-3-small (1536-dim) and bump
 * the `vector(384)` column to `vector(1536)` via a follow-up migration.
 */
final class HeuristicEmbeddingService implements EmbeddingService
{
    private const DIMS = 384;

    public function dimensions(): int
    {
        return self::DIMS;
    }

    public function embed(string $text): array
    {
        $vector = array_fill(0, self::DIMS, 0.0);

        $tokens = preg_split('/[\s\p{P}]+/u', mb_strtolower(trim($text))) ?: [];
        $tokens = array_filter($tokens, static fn (string $t): bool => mb_strlen($t) >= 2);

        if (empty($tokens)) {
            // Avoid all-zero vector (pgvector cosine on zero is undefined).
            $vector[0] = 1.0;

            return $vector;
        }

        foreach ($tokens as $token) {
            $hash = crc32($token);
            $bucket = (int) ($hash % self::DIMS);
            $sign = (($hash >> 16) & 1) === 0 ? 1.0 : -1.0;
            $vector[$bucket] += $sign;
        }

        // L2-normalize so cosine == dot product.
        $magnitude = sqrt(array_sum(array_map(static fn (float $v): float => $v * $v, $vector)));
        if ($magnitude > 0.0) {
            $vector = array_map(static fn (float $v) => $v / $magnitude, $vector);
        }

        return array_values($vector);
    }
}
