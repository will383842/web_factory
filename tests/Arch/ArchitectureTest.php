<?php

declare(strict_types=1);

/*
 * Architectural rules enforced as Pest tests.
 *
 * These rules implement Spec 27 (modular monolith DDD onion architecture):
 *  - Domain is pure: no framework, no infrastructure dependency.
 *  - Repository contracts live in Domain; implementations live in Infrastructure.
 *  - Eloquent is confined to Infrastructure\Persistence\Eloquent.
 *  - Controllers are thin: never touch Eloquent directly.
 *  - Application layer follows CQRS naming.
 *
 * Sprint 0 status: rules pass against an EMPTY skeleton (placeholder interfaces only).
 * Each subsequent sprint must keep these rules green.
 */

// ---------------------------------------------------------------------------
// 1. Domain is framework-agnostic
// ---------------------------------------------------------------------------

arch('domain does not depend on Illuminate framework')
    ->expect('App\Domain')
    ->not->toUse('Illuminate');

arch('domain does not depend on Symfony framework')
    ->expect('App\Domain')
    ->not->toUse('Symfony');

// ---------------------------------------------------------------------------
// 2. Domain does not depend on Infrastructure (onion direction)
// ---------------------------------------------------------------------------

arch('domain does not depend on infrastructure')
    ->expect('App\Domain')
    ->not->toUse('App\Infrastructure');

arch('domain does not depend on Filament')
    ->expect('App\Domain')
    ->not->toUse('Filament');

// ---------------------------------------------------------------------------
// 3. Repository contracts live in Domain and are interfaces
// ---------------------------------------------------------------------------

arch('repository contracts in Domain are interfaces')
    ->expect('App\Domain')
    ->classes()
    ->toBeInterfaces()
    ->ignoring([
        'App\Domain\Identity\Entities',
        'App\Domain\Catalog\Entities',
        'App\Domain\Content\Entities',
        'App\Domain\Marketing\Entities',
        'App\Domain\Billing\Entities',
        'App\Domain\Communication\Entities',
        'App\Domain\Search\Entities',
        'App\Domain\Analytics\Entities',
        'App\Domain\Ai\Entities',
        'App\Domain\Compliance\Entities',
        'App\Domain\Identity\ValueObjects',
        'App\Domain\Catalog\ValueObjects',
        'App\Domain\Content\ValueObjects',
        'App\Domain\Marketing\ValueObjects',
        'App\Domain\Billing\ValueObjects',
        'App\Domain\Communication\ValueObjects',
        'App\Domain\Search\ValueObjects',
        'App\Domain\Analytics\ValueObjects',
        'App\Domain\Ai\ValueObjects',
        'App\Domain\Compliance\ValueObjects',
        'App\Domain\Shared',
        'App\Domain\Identity\Services',
        'App\Domain\Catalog\Services',
        'App\Domain\Content\Services',
        'App\Domain\Marketing\Services',
        'App\Domain\Billing\Services',
        'App\Domain\Communication\Services',
        'App\Domain\Search\Services',
        'App\Domain\Analytics\Services',
        'App\Domain\Ai\Services',
        'App\Domain\Compliance\Services',
        'App\Domain\Identity\Events',
        'App\Domain\Catalog\Events',
        'App\Domain\Content\Events',
        'App\Domain\Marketing\Events',
        'App\Domain\Billing\Events',
        'App\Domain\Communication\Events',
        'App\Domain\Search\Events',
        'App\Domain\Analytics\Events',
        'App\Domain\Ai\Events',
        'App\Domain\Compliance\Events',
        'App\Domain\Identity\Exceptions',
        'App\Domain\Catalog\Exceptions',
        'App\Domain\Content\Exceptions',
        'App\Domain\Marketing\Exceptions',
        'App\Domain\Billing\Exceptions',
        'App\Domain\Communication\Exceptions',
        'App\Domain\Search\Exceptions',
        'App\Domain\Analytics\Exceptions',
        'App\Domain\Ai\Exceptions',
        'App\Domain\Compliance\Exceptions',
    ]);

// ---------------------------------------------------------------------------
// 4. Eloquent is confined to Infrastructure
// ---------------------------------------------------------------------------

arch('Eloquent models live only in Infrastructure\Persistence\Eloquent')
    ->expect('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn([
        'App\Infrastructure\Persistence\Eloquent',
        'App\Models',
    ]);

// ---------------------------------------------------------------------------
// 5. Controllers do not touch Eloquent
// ---------------------------------------------------------------------------

arch('HTTP controllers do not import Eloquent models')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Database\Eloquent');

// ---------------------------------------------------------------------------
// 6 & 7. CQRS naming convention in Application layer
// ---------------------------------------------------------------------------

arch('Application Commands have a Command suffix')
    ->expect('App\Application')
    ->classes()
    ->toHaveSuffix('Command')
    ->ignoring([
        'App\Application\Identity\Queries',
        'App\Application\Catalog\Queries',
        'App\Application\Content\Queries',
        'App\Application\Marketing\Queries',
        'App\Application\Billing\Queries',
        'App\Application\Communication\Queries',
        'App\Application\Search\Queries',
        'App\Application\Analytics\Queries',
        'App\Application\Ai\Queries',
        'App\Application\Compliance\Queries',
        'App\Application\Identity\DTOs',
        'App\Application\Catalog\DTOs',
        'App\Application\Content\DTOs',
        'App\Application\Marketing\DTOs',
        'App\Application\Billing\DTOs',
        'App\Application\Communication\DTOs',
        'App\Application\Search\DTOs',
        'App\Application\Analytics\DTOs',
        'App\Application\Ai\DTOs',
        'App\Application\Compliance\DTOs',
    ]);

// ---------------------------------------------------------------------------
// 8. Pest preset: PHP best-practices
// ---------------------------------------------------------------------------

arch('php best practices')
    ->preset()
    ->php();

// ---------------------------------------------------------------------------
// 9. Pest preset: security baseline
// ---------------------------------------------------------------------------

arch('security baseline')
    ->preset()
    ->security();
