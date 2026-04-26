<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.1 — emitted when a subscription transitions to an active or
 * trialing state for the first time. Listeners feed analytics (MRR/ARR), the
 * onboarding orchestrator (Sprint 13.3) and the notification center.
 */
final class SubscriptionStarted extends DomainEvent
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $projectId,
        public readonly int $customerId,
        public readonly int $planId,
        public readonly string $status,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($occurredAt);
    }

    public function aggregateId(): string
    {
        return (string) $this->subscriptionId;
    }

    public function eventName(): string
    {
        return 'billing.subscription.started';
    }
}
