<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.1 — emitted when an invoice payment fails. The dunning workflow
 * (Sprint 13.4 notifications) listens to this to trigger retry emails.
 */
final class PaymentFailed extends DomainEvent
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly int $subscriptionId,
        public readonly int $amountCents,
        public readonly ?string $reason = null,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($occurredAt);
    }

    public function aggregateId(): string
    {
        return (string) $this->invoiceId;
    }

    public function eventName(): string
    {
        return 'billing.payment.failed';
    }
}
