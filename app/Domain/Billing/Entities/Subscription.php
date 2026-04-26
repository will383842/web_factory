<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entities;

use App\Domain\Billing\Events\SubscriptionCreated;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Money;

final class Subscription extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $planCode,
        public readonly Money $price,
    ) {}

    public static function create(string $id, string $userId, string $planCode, Money $price): self
    {
        $sub = new self($id, $userId, $planCode, $price);
        $sub->recordEvent(new SubscriptionCreated($id, $userId, $planCode, $price));

        return $sub;
    }

    public static function rehydrate(string $id, string $userId, string $planCode, Money $price): self
    {
        return new self($id, $userId, $planCode, $price);
    }
}
