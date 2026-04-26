<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\Marketing\Services\SeoHubAggregator;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Aggregated SEO/AEO health dashboard. Read-only page that surfaces
 * counts + average AEO score + KB chunk count across all projects.
 *
 * The view template is rendered by Filament from a Blade file referenced
 * by `$view` ; for Sprint 11 we keep it inlined via `getViewData()` so
 * the page is fully self-contained — Sprint 14 will introduce a richer
 * Blade view with charts.
 */
final class SeoHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'SEO Hub';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $title = 'SEO / AEO Hub';

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.seo-hub';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $aggregator = app(SeoHubAggregator::class);

        return [
            'summary' => $aggregator->summary(),
            'average_aeo_score' => $aggregator->averageAeoScore(),
        ];
    }
}
