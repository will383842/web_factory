<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Events\LaravelEventDispatcher;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProjectRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Wires Domain contracts (App\Domain\*\Contracts) to their Infrastructure
 * implementations (App\Infrastructure\*).
 *
 * Sprint 1: EventDispatcher.
 * Sprint 2: + UserRepositoryInterface (Identity BC).
 * Sprint 4: + ProjectRepositoryInterface (Catalog BC).
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);
    }
}
