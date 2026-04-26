<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\Blueprint;
use App\Application\Catalog\Services\BlueprintGenerationService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-5 deterministic placeholder for Pipeline Step 2 — produces a
 * fixed-shape blueprint (10 standard pages + 3 journeys + 5 KPIs) that any
 * platform always needs. Sprint 19 will swap to a Claude-driven generator.
 */
final class HeuristicBlueprintGenerationService implements BlueprintGenerationService
{
    public function generate(Project $project): Blueprint
    {
        $pages = [
            ['slug' => 'home', 'title' => $project->name, 'type' => 'home'],
            ['slug' => 'about', 'title' => 'About', 'type' => 'static'],
            ['slug' => 'pricing', 'title' => 'Pricing', 'type' => 'pricing'],
            ['slug' => 'features', 'title' => 'Features', 'type' => 'static'],
            ['slug' => 'blog', 'title' => 'Blog', 'type' => 'index'],
            ['slug' => 'contact', 'title' => 'Contact', 'type' => 'form'],
            ['slug' => 'legal/terms', 'title' => 'Terms', 'type' => 'legal'],
            ['slug' => 'legal/privacy', 'title' => 'Privacy', 'type' => 'legal'],
            ['slug' => 'legal/cookies', 'title' => 'Cookies', 'type' => 'legal'],
            ['slug' => 'sitemap', 'title' => 'Sitemap', 'type' => 'index'],
        ];

        $journeys = [
            ['name' => 'discover', 'steps' => ['home', 'features', 'pricing', 'contact']],
            ['name' => 'convert', 'steps' => ['features', 'pricing', 'contact']],
            ['name' => 'support', 'steps' => ['blog', 'contact']],
        ];

        $kpis = [
            ['key' => 'monthly_active_visitors', 'target' => 5_000],
            ['key' => 'lead_capture_rate', 'target' => 0.025],
            ['key' => 'pricing_to_contact', 'target' => 0.1],
            ['key' => 'organic_traffic_share', 'target' => 0.6],
            ['key' => 'time_to_first_conversion_days', 'target' => 14],
        ];

        return new Blueprint(pages: $pages, journeys: $journeys, kpis: $kpis);
    }
}
