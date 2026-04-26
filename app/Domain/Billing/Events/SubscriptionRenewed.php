<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.1 — emitted when a recurring period rolls over and the next
 * invoice is paid. Used for revenue recognition and renewal emails.
 */
final class SubscriptionRenewed extends DomainEvent
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $invoiceId,
        public readonly int $amountPaidCents,
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
        return 'billing.subscription.renewed';
    }
}
