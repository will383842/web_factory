# ADR 0001 — Use Laravel 13 as the application framework

## Status

Accepted — 2026-04-26 (revised; supersedes the original "Use Laravel 12" decision)

## Context

WebFactory needs a backend framework that supports a modular monolith with strong DDD discipline,
mature ecosystem (queues, websockets, scheduling, ORM, mail, scout, sanctum, telescope), first-class
admin tooling (Filament), and a stable support posture for a multi-year SaaS product.

The realistic candidates were Laravel 13, Laravel 12, Symfony 7, and Node-based alternatives (NestJS).

At the moment of `composer create-project laravel/laravel` (Sprint 0, 2026-04-26), the default
release is Laravel 13.6.0 with PHP 8.4 as the minimum runtime (transitive `symfony/console v8`
requirement). Laravel 13 is current stable on the Laravel release calendar.

## Decision

We adopt **Laravel 13** as the unique backend framework for WebFactory, running on **PHP 8.4**.

## Alternatives considered

- **Laravel 12** — original choice in this ADR. Superseded: pinning to 12 on a fresh project would
  require an explicit `^12.0` constraint and locks us a major version behind without practical
  benefit, since Filament v4, Horizon, Reverb, Telescope, Scout, Sanctum, Pint, Pest all have
  Laravel 13 support out of the box.
- **Symfony 7** — top-tier DDD discipline (Messenger, Doctrine), but slower DX for our use cases,
  smaller pool of admin-panel tooling at the level of Filament.
- **NestJS / TypeScript stack** — appealing single-language full-stack, but loses parity with the
  Laravel ecosystem we are reusing from SOS Expat (Mission Control, Backlink Engine, Telegram Engine
  are all Laravel) and from the Laravel admin tooling (Filament).

## Consequences

- Positives:
  - Reuse of internal know-how across SOS Expat ecosystem services.
  - Filament v4 alignment, Horizon, Reverb, Scout, Telescope, Sanctum, Pint out of the box.
  - Fast iteration via Artisan + tight integration with Inertia/Vue + Vite.
  - Latest framework features (PHP 8.4 property hooks, asymmetric visibility, etc.).
- Negatives:
  - PHP-only backend; second runtime (Node) for SSR/ESM tooling.
  - Some "Laravel magic" must be disciplined inside our DDD layers (see ADR 0007, 0008).
  - PHP 8.4 minimum: required `opcache.jit = off` in our php.ini (tracing JIT crashes PHP-FPM
    during Laravel boot in some configurations; safe to revisit when JIT stability matures).
- Neutral:
  - Tied to the Laravel release calendar; we will track major upgrades sprint by sprint.

## References

- Spec 01 — Stack & architecture
- Spec 27 — Architecture & scalability (DDD layers must contain framework usage to specific layers)
- `docker/php/Dockerfile` (`ARG PHP_VERSION=8.4`)
- `docker/php/php.ini` (`opcache.jit = off`)
