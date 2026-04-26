<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.1 — emitted when a subscription is canceled (immediately or at
 * period end). Used for churn analytics and exit-survey triggers.
 */
final class SubscriptionCanceled extends DomainEvent
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly bool $cancelAtPeriodEnd,
        public readonly ?string $reason = null,
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
        return 'billing.subscription.canceled';
    }
}
