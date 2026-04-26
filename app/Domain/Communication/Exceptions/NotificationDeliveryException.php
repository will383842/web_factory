<?php

declare(strict_types=1);

namespace App\Domain\Communication\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class NotificationDeliveryException extends DomainException
{
    public function errorCode(): string
    {
        return 'communication.notification.delivery_failed';
    }
}
