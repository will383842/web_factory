<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Application\Billing\DTOs\CheckoutSession;
use App\Application\Billing\Services\BillingGateway;
use App\Models\BillingCustomer;
use App\Models\BillingInvoice;
use App\Models\BillingPlan;
use App\Models\BillingSubscription;
use App\Models\BillingWebhookEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sprint-13.1 placeholder implementation of {@see BillingGateway}.
 *
 * Generates synthetic Stripe-shaped IDs, persists state locally, and never
 * touches the network. The DB writes mimic what real Stripe webhooks would
 * eventually trigger so the rest of the system (admin, dashboards, exports)
 * works end-to-end out of the box. Sprint 16 swaps this for a real
 * stripe-php-backed adapter; the port stays unchanged.
 */
final class PlaceholderStripeBillingGateway implements BillingGateway
{
    public function name(): string
    {
        return BillingWebhookEvent::PROVIDER_STRIPE;
    }

    public function createCheckoutSession(BillingCustomer $customer, BillingPlan $plan): CheckoutSession
    {
        $sessionId = 'cs_test_'.Str::random(24);

        DB::transaction(function () use ($customer, $plan): void {
            if ($customer->stripe_customer_id === null) {
                $customer->forceFill(['stripe_customer_id' => 'cus_test_'.Str::random(14)])->save();
            }

            BillingSubscription::query()->updateOrCreate(
                [
                    'project_id' => $customer->project_id,
                    'customer_id' => $customer->getKey(),
                    'plan_id' => $plan->getKey(),
                ],
                [
                    'status' => BillingSubscription::STATUS_ACTIVE,
                    'current_period_start' => now(),
                    'current_period_end' => $this->nextPeriodEnd($plan->billing_cycle),
                    'stripe_subscription_id' => 'sub_test_'.Str::random(14),
                ],
            );
        });

        return new CheckoutSession(
            sessionId: $sessionId,
            redirectUrl: 'https://checkout.stripe.test/'.$sessionId,
            provider: $this->name(),
        );
    }

    public function cancelSubscription(BillingSubscription $subscription, bool $atPeriodEnd = true): void
    {
        $subscription->forceFill([
            'cancel_at_period_end' => $atPeriodEnd,
            'canceled_at' => now(),
            'status' => $atPeriodEnd ? $subscription->status : BillingSubscription::STATUS_CANCELED,
            'ended_at' => $atPeriodEnd ? null : now(),
        ])->save();
    }

    public function refundInvoice(int $invoiceId, ?int $amountCents = null): void
    {
        $invoice = BillingInvoice::query()->findOrFail($invoiceId);
        $invoice->forceFill([
            'status' => BillingInvoice::STATUS_VOID,
            'amount_paid_cents' => 0,
        ])->save();
    }

    private function nextPeriodEnd(string $cycle): Carbon
    {
        return match ($cycle) {
            BillingPlan::CYCLE_YEARLY => now()->addYear(),
            BillingPlan::CYCLE_ONE_TIME => now()->addCentury(),
            default => now()->addMonth(),
        };
    }
}
