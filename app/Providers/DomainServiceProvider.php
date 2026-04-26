<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Events\LaravelEventDispatcher;
use Illuminate\Support\ServiceProvider;

/**
 * Wires Domain contracts (App\Domain\*\Contracts) to their Infrastructure
 * implementations (App\Infrastructure\*).
 *
 * Sprint 1 scope: only the EventDispatcher binding is wired — repository
 * bindings are added per BC starting Sprint 2 (when concrete Eloquent
 * implementations exist).
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);
    }
}
