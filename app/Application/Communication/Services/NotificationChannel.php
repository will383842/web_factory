<?php

declare(strict_types=1);

namespace App\Application\Communication\Services;

use App\Application\Communication\DTOs\DispatchResult;
use App\Application\Communication\DTOs\NotificationMessage;
use App\Infrastructure\Communication\LogNotificationChannel;

/**
 * Sprint 13.4 — Port for any notification channel.
 *
 * Sprint-13.4 ships a single {@see LogNotificationChannel}
 * that satisfies every `name()` registered in the registry — it just writes
 * the message to the Laravel log so devs can iterate on templates without
 * any provider keys. Sprint 16 swaps each name for a real adapter:
 *  - in-app   → in-database notifications
 *  - email    → PostmarkAdapter (or Brevo / Resend driver-pickable)
 *  - sms      → TwilioSmsAdapter
 *  - whatsapp → TwilioWhatsAppAdapter
 *  - push_web → WebPushAdapter (VAPID)
 *  - push_mob → OneSignalAdapter
 *  - telegram → TelegramBotAdapter
 *  - slack    → SlackWebhookAdapter
 *  - discord  → DiscordWebhookAdapter
 */
interface NotificationChannel
{
    public function name(): string;

    public function send(NotificationMessage $message): DispatchResult;
}
