<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SubscriptionNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'billing.subscription.not_found';
    }
}
