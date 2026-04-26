<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 2 — blueprint (pages, parcours, KPI).
 */
final readonly class Blueprint
{
    /**
     * @param list<array{slug: string, title: string, type: string}> $pages
     * @param list<array{name: string, steps: list<string>}> $journeys
     * @param list<array{key: string, target: string|float|int}> $kpis
     */
    public function __construct(
        public array $pages,
        public array $journeys,
        public array $kpis,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'pages' => $this->pages,
            'journeys' => $this->journeys,
            'kpis' => $this->kpis,
        ];
    }
}
