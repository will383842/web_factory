<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

use App\Application\Billing\Services\BillingGateway;

/**
 * Returned by {@see BillingGateway::createCheckoutSession()}.
 *
 * `redirectUrl` is the provider-hosted checkout page (Stripe Checkout, Paddle
 * Inline, etc.). The placeholder Sprint-13 adapter returns a synthetic URL so
 * the controller wiring is testable without leaving the local environment.
 */
final readonly class CheckoutSession
{
    public function __construct(
        public string $sessionId,
        public string $redirectUrl,
        public string $provider,
    ) {}
}
