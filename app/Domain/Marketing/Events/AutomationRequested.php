<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 14 — emitted when a public visitor submits the "Demande
 * d'automatisation" CTA modal. Listened by Sprint 13.4 dispatcher to fan
 * the lead out to email + Telegram.
 */
final class AutomationRequested extends DomainEvent
{
    public function __construct(
        public readonly int $automationRequestId,
        public readonly ?int $projectId,
        public readonly string $email,
        public readonly string $category,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($occurredAt);
    }

    public function aggregateId(): string
    {
        return (string) $this->automationRequestId;
    }

    public function eventName(): string
    {
        return 'marketing.automation.requested';
    }
}
