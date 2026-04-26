<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Money;

final class SubscriptionCreated extends DomainEvent
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $userId,
        public readonly string $planCode,
        public readonly Money $price,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->subscriptionId;
    }

    public function eventName(): string
    {
        return 'billing.subscription.created';
    }
}
