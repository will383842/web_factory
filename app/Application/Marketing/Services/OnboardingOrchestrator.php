<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Models\OnboardingFlow;
use App\Models\User;
use App\Models\UserOnboardingProgress;

/**
 * Sprint 13.3 — orchestrates a user's progress through an onboarding flow.
 *
 * Listeners auto-call `markStepCompleted()` when a domain event fires that
 * matches a step key (Sprint 13.4 wires SubscriptionStarted → step
 * `subscribed`, MemberJoined → step `team_joined`, etc.). Sprint 16 will
 * add drip-email triggers when a user lingers under a threshold for N days.
 */
final class OnboardingOrchestrator
{
    public function __construct(private readonly ActivationScoreCalculator $calculator) {}

    public function start(User $user, OnboardingFlow $flow): UserOnboardingProgress
    {
        $progress = UserOnboardingProgress::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'flow_id' => $flow->getKey()],
            ['completed_steps' => [], 'score' => 0, 'started_at' => now()],
        );

        return $progress->fresh() ?? $progress;
    }

    public function markStepCompleted(User $user, OnboardingFlow $flow, string $stepKey): UserOnboardingProgress
    {
        $progress = UserOnboardingProgress::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'flow_id' => $flow->getKey()],
            ['completed_steps' => [], 'score' => 0, 'started_at' => now()],
        );

        $completed = array_values(array_unique(array_merge(
            $progress->completed_steps->toArray(),
            [$stepKey],
        )));

        $score = $this->calculator->calculate($flow, $completed);

        $progress->forceFill([
            'completed_steps' => $completed,
            'score' => $score,
            'completed_at' => $score >= 100 ? now() : null,
        ])->save();

        return $progress->fresh() ?? $progress;
    }
}
