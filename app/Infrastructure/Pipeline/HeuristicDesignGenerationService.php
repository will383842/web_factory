<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\Blueprint;
use App\Application\Catalog\DTOs\DesignSystem;
use App\Application\Catalog\Services\DesignGenerationService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-5 deterministic placeholder for Pipeline Step 3 — emits a fixed
 * indigo/slate token set + 8 named mockups (HTML stubs). Sprint 19 will
 * swap to a Claude-driven generator that picks tokens from the brief.
 */
final class HeuristicDesignGenerationService implements DesignGenerationService
{
    public function generate(Project $project, Blueprint $blueprint): DesignSystem
    {
        $tokens = [
            'color.primary' => '#4F46E5',
            'color.secondary' => '#0F172A',
            'color.accent' => '#F59E0B',
            'color.background' => '#FFFFFF',
            'color.foreground' => '#0F172A',
            'font.heading' => 'Inter, system-ui, sans-serif',
            'font.body' => 'Inter, system-ui, sans-serif',
            'spacing.unit' => '0.25rem',
            'radius.sm' => '0.25rem',
            'radius.md' => '0.5rem',
            'radius.lg' => '1rem',
        ];

        $mockupNames = [
            'home-hero',
            'home-features',
            'home-cta',
            'pricing-table',
            'blog-index',
            'blog-article',
            'contact-form',
            'legal-page',
        ];

        $mockups = array_map(
            static fn (string $name): array => [
                'name' => $name,
                'html' => sprintf(
                    '<section data-mockup="%s" data-project="%s"><h1>%s</h1></section>',
                    $name,
                    htmlspecialchars($project->slug->value, ENT_QUOTES),
                    htmlspecialchars($project->name, ENT_QUOTES),
                ),
            ],
            $mockupNames,
        );

        return new DesignSystem(tokens: $tokens, mockups: $mockups);
    }
}
