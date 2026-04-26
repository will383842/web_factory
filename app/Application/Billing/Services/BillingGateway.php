<?php

declare(strict_types=1);

namespace App\Application\Billing\Services;

use App\Application\Billing\DTOs\CheckoutSession;
use App\Models\BillingCustomer;
use App\Models\BillingPlan;
use App\Models\BillingSubscription;

/**
 * Port for billing providers (Stripe, Paddle, LemonSqueezy, Mollie, …).
 *
 * Sprint-13.1 default impl is a placeholder gateway that does not call any
 * external API — it generates synthetic IDs so the rest of the application
 * (Filament admin, controllers, Pest tests) can be wired up without touching
 * a real provider account. Sprint 16 (Deployment) swaps in the real
 * StripeBillingGateway with the stripe-php SDK.
 *
 * The `name()` method returns the provider slug used for telemetry and the
 * webhook idempotency key (`provider`, `event_id`).
 */
interface BillingGateway
{
    public function name(): string;

    public function createCheckoutSession(BillingCustomer $customer, BillingPlan $plan): CheckoutSession;

    public function cancelSubscription(BillingSubscription $subscription, bool $atPeriodEnd = true): void;

    public function refundInvoice(int $invoiceId, ?int $amountCents = null): void;
}
