<?php

declare(strict_types=1);

namespace App\Application\Communication\DTOs;

/**
 * Sprint 13.4 — Provider-agnostic notification payload.
 *
 * `recipient` is whatever the channel needs (email address, phone E.164 number,
 * Telegram chat_id, Slack webhook URL, Discord channel id, FCM token).
 * `subject` is optional (some channels — push web, telegram, in-app — do
 * not have one).
 */
final readonly class NotificationMessage
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function __construct(
        public string $channel,
        public string $eventType,
        public string $recipient,
        public string $body,
        public ?string $subject = null,
        public array $payload = [],
        public ?int $userId = null,
        public ?int $templateId = null,
    ) {}
}
