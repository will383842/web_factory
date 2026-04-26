<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 3 — design system + 8 mockups.
 */
final readonly class DesignSystem
{
    /**
     * @param array<string, string> $tokens colors, fonts, spacing
     * @param list<array{name: string, html: string}> $mockups 8 lightweight HTML mockups
     */
    public function __construct(
        public array $tokens,
        public array $mockups,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'tokens' => $this->tokens,
            'mockups_count' => count($this->mockups),
            // Mockups themselves can be megabytes — store an index here, full
            // payload goes to MinIO in Sprint 6.
            'mockups_summary' => array_map(
                static fn (array $m): string => $m['name'],
                $this->mockups,
            ),
        ];
    }
}
