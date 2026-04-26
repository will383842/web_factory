<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 4b — completeness scoring of a {@see BriefBundle}.
 *
 * The pipeline gate accepts the brief only if `score >= 85`. Lower scores
 * surface as `gaps` (human-readable explanations) so the customer can fill
 * the missing bits before re-submitting.
 */
final readonly class BriefScore
{
    public const PASSING_THRESHOLD = 85;

    /**
     * @param list<string> $gaps
     * @param list<string> $strengths
     */
    public function __construct(
        public int $score,
        public array $gaps,
        public array $strengths,
    ) {}

    public function passes(): bool
    {
        return $this->score >= self::PASSING_THRESHOLD;
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'score' => $this->score,
            'passes' => $this->passes(),
            'gaps' => $this->gaps,
            'strengths' => $this->strengths,
        ];
    }
}
