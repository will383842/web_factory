<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\IdeaAnalysisResult;
use App\Application\Catalog\Services\IdeaAnalysisService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-5 deterministic placeholder for Pipeline Step 1.
 *
 * Heuristic scoring formulae:
 *  - viralityScore = clamp(0,100, 30 + 5*words(name) + 2*words(description) + locale_bonus)
 *  - valueScore    = clamp(0,100, 40 + (description_length / 20) + has_primary_domain*10)
 *
 * Replace with Claude-API powered adapter in Sprint 19 (see Spec 24 Feature 1).
 */
final class HeuristicIdeaAnalysisService implements IdeaAnalysisService
{
    private const VIRAL_KEYWORDS = ['ai', 'social', 'free', 'tool', 'instant', 'no-code', 'collab'];

    public function analyze(Project $project): IdeaAnalysisResult
    {
        $description = (string) $project->description;
        $name = $project->name;
        $localeBonus = $project->locale->language() === 'en' ? 10 : 5;

        $virality = 30
            + (5 * str_word_count($name))
            + (2 * str_word_count($description))
            + $localeBonus
            + ($this->keywordHits($name.' '.$description) * 8);

        $value = 40
            + (int) (mb_strlen($description) / 20)
            + ($project->primaryDomain !== null ? 10 : 0);

        $clarifications = [];
        if (mb_strlen($description) < 60) {
            $clarifications[] = 'Description is short — consider elaborating on the target persona and pain point.';
        }
        if ($project->primaryDomain === null) {
            $clarifications[] = 'No primary domain set — pick one to unlock pre-launch SEO seeding.';
        }

        return new IdeaAnalysisResult(
            viralityScore: max(0, min(100, $virality)),
            valueScore: max(0, min(100, $value)),
            clarifications: $clarifications,
            strengths: $this->buildStrengths($project),
            weaknesses: $this->buildWeaknesses($project),
        );
    }

    private function keywordHits(string $haystack): int
    {
        $haystack = mb_strtolower($haystack);
        $hits = 0;
        foreach (self::VIRAL_KEYWORDS as $kw) {
            if (str_contains($haystack, $kw)) {
                $hits++;
            }
        }

        return $hits;
    }

    /**
     * @return list<string>
     */
    private function buildStrengths(Project $project): array
    {
        $out = [];
        if ($project->primaryDomain !== null) {
            $out[] = 'Primary domain reserved.';
        }
        if (mb_strlen((string) $project->description) >= 120) {
            $out[] = 'Description is rich enough to feed downstream generators.';
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function buildWeaknesses(Project $project): array
    {
        $out = [];
        if ($project->locale->region() === null) {
            $out[] = 'Locale lacks a region tag — local-pack SEO will be weaker.';
        }

        return $out;
    }
}
