<?php

declare(strict_types=1);

namespace App\Domain\Shared\Entities;

use App\Domain\Shared\Contracts\EventDispatcher;
use App\Domain\Shared\Events\DomainEvent;

/**
 * Base for aggregate roots that need to record domain events.
 *
 * The Application layer calls {@see flushEvents()} after persistence to hand
 * the recorded events over to an {@see EventDispatcher}.
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    final protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Pop and return all events recorded since the last flush.
     *
     * @return list<DomainEvent>
     */
    final public function flushEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    /**
     * @return list<DomainEvent>
     */
    final public function pendingEvents(): array
    {
        return $this->recordedEvents;
    }
}
