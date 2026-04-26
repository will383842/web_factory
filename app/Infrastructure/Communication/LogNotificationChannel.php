<?php

declare(strict_types=1);

namespace App\Infrastructure\Communication;

use App\Application\Communication\DTOs\DispatchResult;
use App\Application\Communication\DTOs\NotificationMessage;
use App\Application\Communication\Services\NotificationChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sprint-13.4 placeholder channel.
 *
 * One instance is registered per real channel name (`in_app` / `email` /
 * `sms` / `whatsapp` / `push_web` / `push_mob` / `telegram` / `slack` /
 * `discord`). Every send writes a Laravel log line and returns a synthetic
 * external_id — Sprint 16 swaps each name for the matching real adapter
 * (Postmark, Twilio, OneSignal, ...) without touching the dispatcher.
 */
final class LogNotificationChannel implements NotificationChannel
{
    public function __construct(private readonly string $name) {}

    public function name(): string
    {
        return $this->name;
    }

    public function send(NotificationMessage $message): DispatchResult
    {
        $externalId = $this->name.'_'.Str::random(12);

        Log::info('notification.sent', [
            'channel' => $this->name,
            'event_type' => $message->eventType,
            'recipient' => $message->recipient,
            'subject' => $message->subject,
            'body_excerpt' => Str::limit($message->body, 120),
            'external_id' => $externalId,
            'user_id' => $message->userId,
        ]);

        return new DispatchResult(success: true, externalId: $externalId);
    }
}
