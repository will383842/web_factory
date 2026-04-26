<?php

declare(strict_types=1);

namespace App\Application\Marketing\DTOs;

final readonly class InternalLinkSuggestion
{
    public function __construct(
        public string $sourceType,    // article / page / faq
        public int $sourceId,
        public string $anchorHint,    // suggested anchor text (1-3 words)
        public string $targetSlug,    // target URL slug
        public float $similarity,     // 0..1 cosine similarity
    ) {}
}
