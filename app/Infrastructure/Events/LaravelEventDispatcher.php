<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Shared\Contracts\EventDispatcher;
use App\Domain\Shared\Events\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Adapter that forwards domain events to Laravel's event dispatcher.
 *
 * The Domain layer depends only on {@see EventDispatcher} (its own interface);
 * this Infrastructure adapter satisfies the contract using the framework.
 */
final readonly class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(private Dispatcher $laravelDispatcher) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->laravelDispatcher->dispatch($event);
    }

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
