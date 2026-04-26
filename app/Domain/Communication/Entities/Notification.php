<?php

declare(strict_types=1);

namespace App\Domain\Communication\Entities;

use App\Domain\Communication\Events\NotificationSent;
use App\Domain\Shared\Entities\AggregateRoot;

final class Notification extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $recipient,
        public readonly string $channel,
        public readonly string $body,
    ) {}

    public static function send(string $id, string $recipient, string $channel, string $body): self
    {
        $notification = new self($id, $recipient, $channel, $body);
        $notification->recordEvent(new NotificationSent($id, $recipient, $channel));

        return $notification;
    }

    public static function rehydrate(string $id, string $recipient, string $channel, string $body): self
    {
        return new self($id, $recipient, $channel, $body);
    }
}
