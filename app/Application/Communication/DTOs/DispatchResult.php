<?php

declare(strict_types=1);

namespace App\Application\Communication\DTOs;

use App\Application\Communication\Services\NotificationChannel;

/**
 * Returned by every {@see NotificationChannel}
 * `send()` call. `externalId` is the provider message id (Postmark / Twilio
 * SID / OneSignal / Telegram message_id) used by inbound delivery webhooks.
 */
final readonly class DispatchResult
{
    public function __construct(
        public bool $success,
        public ?string $externalId = null,
        public ?string $errorMessage = null,
    ) {}
}
