<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 6 — multilingual content production.
 *
 * `pageIds`, `articleIds`, `faqIds` are the persisted IDs created across all
 * locales. `producedLocales` is the deduped list of locales actually written
 * (may be smaller than the input set if a locale lacks a translation).
 */
final readonly class ContentBundle
{
    /**
     * @param list<int> $pageIds
     * @param list<int> $articleIds
     * @param list<int> $faqIds
     * @param list<string> $producedLocales
     */
    public function __construct(
        public array $pageIds,
        public array $articleIds,
        public array $faqIds,
        public array $producedLocales,
    ) {}

    /** @return array<string, mixed> */
    public function toMetadataArray(): array
    {
        return [
            'pages_count' => count($this->pageIds),
            'articles_count' => count($this->articleIds),
            'faqs_count' => count($this->faqIds),
            'produced_locales' => $this->producedLocales,
        ];
    }
}
