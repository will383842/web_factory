<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 1 — analyse idée + scoring.
 *
 * In Sprint 5 the underlying analyser is a deterministic heuristic
 * (string length, keyword density, locale weight). The contract is wired
 * via dependency injection so Sprint 19 can drop in the Claude-API powered
 * implementation without touching callers.
 */
final readonly class IdeaAnalysisResult
{
    /**
     * @param list<string> $clarifications open questions surfaced for the customer
     * @param list<string> $strengths
     * @param list<string> $weaknesses
     */
    public function __construct(
        public int $viralityScore,
        public int $valueScore,
        public array $clarifications,
        public array $strengths,
        public array $weaknesses,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'virality_score' => $this->viralityScore,
            'value_score' => $this->valueScore,
            'clarifications' => $this->clarifications,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
        ];
    }
}
