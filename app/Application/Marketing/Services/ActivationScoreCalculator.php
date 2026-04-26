<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Models\OnboardingFlow;

/**
 * Sprint 13.3 — turns the (flow.steps, completed_steps) pair into a 0-100
 * weighted activation score.
 *
 * If a step omits `weight`, it counts as 1. Score = sum(weights of completed
 * steps) / sum(all weights) * 100 — rounded to the nearest int. Empty flows
 * return 0.
 */
final class ActivationScoreCalculator
{
    /**
     * @param array<int, string> $completedKeys
     */
    public function calculate(OnboardingFlow $flow, array $completedKeys): int
    {
        $totalWeight = 0;
        $earnedWeight = 0;
        $completed = array_flip($completedKeys);

        foreach ($flow->steps as $step) {
            $weight = (int) ($step['weight'] ?? 1);
            $totalWeight += $weight;

            if (isset($completed[(string) $step['key']])) {
                $earnedWeight += $weight;
            }
        }

        if ($totalWeight === 0) {
            return 0;
        }

        return (int) round(($earnedWeight / $totalWeight) * 100);
    }
}
