<?php

declare(strict_types=1);

namespace App\Application\Billing\Services;

use App\Application\Billing\DTOs\WebhookProcessingResult;

/**
 * Port for the provider-agnostic webhook intake.
 *
 * Implementations MUST be idempotent on the (provider, event_id) pair —
 * receiving the same event twice MUST produce zero additional side-effects.
 * The Sprint-13.1 implementation persists every event in `billing_webhook_events`
 * and short-circuits when a row already exists.
 *
 * Sprint 16 will add HMAC signature verification (Stripe-Signature header etc.)
 * inside controllers BEFORE calling this processor.
 */
interface BillingWebhookProcessor
{
    /**
     * @param array<string, mixed> $payload decoded JSON body of the provider webhook
     */
    public function process(string $provider, array $payload): WebhookProcessingResult;
}
