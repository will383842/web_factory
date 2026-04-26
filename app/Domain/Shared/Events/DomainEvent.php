<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use DateTimeImmutable;

/**
 * Marker base for all domain events.
 *
 * Domain events are produced by aggregates as a side-effect of state changes
 * and dispatched by the application layer after persistence has succeeded.
 * They are framework-agnostic (no Illuminate dependency) — adapters in
 * App\Infrastructure forward them to PSR-14 / Laravel listeners.
 */
abstract class DomainEvent
{
    private DateTimeImmutable $occurredAt;

    public function __construct(?DateTimeImmutable $occurredAt = null)
    {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Identifier of the aggregate that recorded this event.
     */
    abstract public function aggregateId(): string;

    /**
     * Stable, dot-notation event name (e.g., `identity.user.registered`).
     */
    abstract public function eventName(): string;
}
