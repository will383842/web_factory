<?php

declare(strict_types=1);

namespace App\Domain\Communication\Events;

use App\Domain\Shared\Events\DomainEvent;

final class NotificationSent extends DomainEvent
{
    public function __construct(
        public readonly string $notificationId,
        public readonly string $recipient,
        public readonly string $channel,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->notificationId;
    }

    public function eventName(): string
    {
        return 'communication.notification.sent';
    }
}
