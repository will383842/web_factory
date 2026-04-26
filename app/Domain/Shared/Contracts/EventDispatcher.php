<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Domain-level dispatcher contract.
 *
 * Implementations live in App\Infrastructure (LaravelEventDispatcher) so that
 * the Domain stays free of Illuminate dependencies (see ADR 0007/0008).
 */
interface EventDispatcher
{
    public function dispatch(DomainEvent $event): void;

    /**
     * @param iterable<DomainEvent> $events
     */
    public function dispatchAll(iterable $events): void;
}
