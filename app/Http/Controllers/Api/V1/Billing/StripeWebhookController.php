<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Application\Billing\Services\BillingWebhookProcessor;
use App\Http\Controllers\Controller;
use App\Models\BillingWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sprint 13.1 — Stripe webhook intake.
 *
 * Sprint 16 will add Stripe-Signature HMAC verification at the top of this
 * controller (using the live `STRIPE_WEBHOOK_SECRET`). For now we accept any
 * payload — the (provider, event_id) idempotency layer behind us guarantees
 * we never double-process even with retries.
 */
final class StripeWebhookController extends Controller
{
    public function __construct(private readonly BillingWebhookProcessor $processor) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $result = $this->processor->process(BillingWebhookEvent::PROVIDER_STRIPE, $payload);

        if (! $result->accepted) {
            return response()->json([
                'accepted' => false,
                'error' => $result->errorMessage,
            ], 422);
        }

        return response()->json([
            'accepted' => true,
            'idempotent' => $result->idempotent,
            'event_id' => $result->eventId,
            'event_type' => $result->eventType,
        ]);
    }
}
