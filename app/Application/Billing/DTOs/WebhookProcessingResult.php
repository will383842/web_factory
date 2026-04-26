<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

use App\Application\Billing\Services\BillingWebhookProcessor;

/**
 * Returned by {@see BillingWebhookProcessor::process()}.
 *
 * `idempotent` distinguishes a real first-time processing (`true` if we just
 * stored a fresh row, `false` if we found the (provider, event_id) pair
 * already processed and skipped the side-effects).
 */
final readonly class WebhookProcessingResult
{
    public function __construct(
        public bool $accepted,
        public bool $idempotent,
        public string $eventId,
        public string $eventType,
        public ?string $errorMessage = null,
    ) {}
}
