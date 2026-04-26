<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

use App\Domain\Billing\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findById(string $id): ?Subscription;

    /**
     * @return list<Subscription>
     */
    public function findActiveByUserId(string $userId): array;

    public function save(Subscription $subscription): void;
}
