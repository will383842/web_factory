<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Application\Billing\DTOs\WebhookProcessingResult;
use App\Application\Billing\Services\BillingWebhookProcessor;
use App\Models\BillingWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Sprint 13.1 — provider-agnostic, idempotent webhook intake.
 *
 * Strategy: a check-first lookup on (provider, event_id), then a guarded INSERT
 * inside a savepoint. If two concurrent retries race past the SELECT, the
 * UNIQUE index still wins — the loser's savepoint rolls back without aborting
 * the surrounding transaction (matters under RefreshDatabase tests).
 *
 * Real side-effects per event_type land in Sprint 16 alongside the stripe-php
 * SDK; for Sprint 13.1 we only own the audit trail + idempotency contract.
 */
final class IdempotentBillingWebhookProcessor implements BillingWebhookProcessor
{
    public function process(string $provider, array $payload): WebhookProcessingResult
    {
        $eventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? 'unknown');

        if ($eventId === '') {
            return new WebhookProcessingResult(
                accepted: false,
                idempotent: false,
                eventId: '',
                eventType: $eventType,
                errorMessage: 'Missing event id in payload',
            );
        }

        $existing = BillingWebhookEvent::query()
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->exists();

        if ($existing) {
            return new WebhookProcessingResult(
                accepted: true,
                idempotent: true,
                eventId: $eventId,
                eventType: $eventType,
            );
        }

        try {
            DB::transaction(function () use ($provider, $eventId, $eventType, $payload): void {
                BillingWebhookEvent::query()->create([
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'received_at' => now(),
                    'processed_at' => now(),
                ]);
            });
        } catch (QueryException) {
            // Lost the race: another worker inserted the same (provider, event_id).
            // The savepoint rolled back; the row is already there → idempotent.
            return new WebhookProcessingResult(
                accepted: true,
                idempotent: true,
                eventId: $eventId,
                eventType: $eventType,
            );
        } catch (Throwable $e) {
            return new WebhookProcessingResult(
                accepted: false,
                idempotent: false,
                eventId: $eventId,
                eventType: $eventType,
                errorMessage: $e->getMessage(),
            );
        }

        return new WebhookProcessingResult(
            accepted: true,
            idempotent: false,
            eventId: $eventId,
            eventType: $eventType,
        );
    }
}
