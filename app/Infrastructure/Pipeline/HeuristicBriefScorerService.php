<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\BriefBundle;
use App\Application\Catalog\DTOs\BriefScore;
use App\Application\Catalog\Services\BriefScorerService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-6 deterministic completeness scorer — evaluates a {@see BriefBundle}
 * on six axes (overall coverage, content depth, branding, SEO, deployment,
 * accessibility), returning a 0-100 score plus human-readable gaps.
 */
final class HeuristicBriefScorerService implements BriefScorerService
{
    private const REQUIRED_FILES = [
        'README.md',
        'blueprint.json',
        'design/tokens.json',
        'design/mockups.json',
        'analysis.json',
        '.env.example',
        'composer.json.tpl',
        'docs/architecture.md',
        'docs/seo.md',
        'docs/deploy.md',
    ];

    public function score(Project $project, BriefBundle $bundle): BriefScore
    {
        $gaps = [];
        $strengths = [];

        $score = 0;

        // Axis 1 — required files presence (40 pts)
        $present = 0;
        foreach (self::REQUIRED_FILES as $required) {
            if (isset($bundle->files[$required])) {
                $present++;
            } else {
                $gaps[] = "Missing required file `{$required}`.";
            }
        }
        $score += (int) round((40 * $present) / count(self::REQUIRED_FILES));

        // Axis 2 — at least 8 page briefs (15 pts)
        $pageBriefs = count(array_filter(
            array_keys($bundle->files),
            static fn (string $p): bool => str_starts_with($p, 'pages/'),
        ));
        if ($pageBriefs >= 8) {
            $score += 15;
            $strengths[] = "{$pageBriefs} page briefs covered.";
        } else {
            $gaps[] = "Only {$pageBriefs} page briefs (target ≥ 8).";
            $score += (int) round(15 * $pageBriefs / 8);
        }

        // Axis 3 — at least 8 mockups (15 pts)
        $mockups = count(array_filter(
            array_keys($bundle->files),
            static fn (string $p): bool => str_starts_with($p, 'mockups/'),
        ));
        if ($mockups >= 8) {
            $score += 15;
        } else {
            $gaps[] = "Only {$mockups} mockups (target ≥ 8).";
        }

        // Axis 4 — README has body (10 pts)
        if (isset($bundle->files['README.md']) && mb_strlen($bundle->files['README.md']) > 80) {
            $score += 10;
            $strengths[] = 'README contains a real description.';
        } else {
            $gaps[] = 'README is too short (< 80 chars).';
        }

        // Axis 5 — virality score gate (10 pts) — uses Project meta
        if ($project->viralityScore >= 60) {
            $score += 10;
        } else {
            $gaps[] = "Virality score is {$project->viralityScore}/100 (target ≥ 60).";
        }

        // Axis 6 — value score gate (10 pts)
        if ($project->valueScore >= 50) {
            $score += 10;
        } else {
            $gaps[] = "Value score is {$project->valueScore}/100 (target ≥ 50).";
        }

        $score = max(0, min(100, $score));

        return new BriefScore(score: $score, gaps: $gaps, strengths: $strengths);
    }
}
