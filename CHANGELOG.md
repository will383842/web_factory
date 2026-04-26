# Changelog

All notable changes to WebFactory are documented here. Format follows [Keep a Changelog],
and this project adheres to [Semantic Versioning].

## [Unreleased]

### Added

- Sprint 1 — Architecture squelette:
  - **Shared kernel** (`app/Domain/Shared`):
    - `Events/DomainEvent` (abstract), `Contracts/EventDispatcher` (interface)
    - `Entities/AggregateRoot` (event recording with `recordEvent`/`flushEvents`/`pendingEvents`)
    - `Exceptions/DomainException` (abstract base + `errorCode()`)
    - Value Objects: `Money` + `Currency`, `Url`, `Locale` (with city tag), `Slug` (ASCII)
    - 5 dedicated `Exceptions/` (InvalidCurrency/MoneyCurrencyMismatch/InvalidUrl/InvalidLocale/InvalidSlug)
  - **11 Bounded Contexts** scaffolded with the 4-file pattern (Entity / Event / Exception / RepositoryInterface):
    - `Identity` (User aggregate, Email VO, UserRegistered event)
    - `Catalog` (Product aggregate, ProductCreated event — reference example)
    - `Content` (Page aggregate, PagePublished event)
    - `Marketing` (Conversion aggregate, ConversionTracked event)
    - `Billing` (Subscription aggregate, SubscriptionCreated event)
    - `Communication` (Notification aggregate, NotificationSent event)
    - `Search` (SearchIndex aggregate, IndexUpdated event)
    - `Analytics` (MetricEvent aggregate, MetricsRecorded event)
    - `Ai` (KnowledgeChunk aggregate w/ vector embedding, EmbeddingGenerated event)
    - `Compliance` (AuditLog aggregate, ConsentRecorded event)
  - **Infrastructure** adapter `LaravelEventDispatcher` (forwards to Laravel's `Dispatcher`)
  - **ServiceProvider** `DomainServiceProvider` registered in `bootstrap/providers.php` (binds `EventDispatcher` → `LaravelEventDispatcher`)
  - **ArchTest** extended from 9 → 14 rules: every Domain event extends `DomainEvent`,
    every Identity/Catalog exception extends `DomainException`, Shared+Identity VOs are
    `final`, no Guzzle/Symfony HttpClient in Domain, Application is also infrastructure-free
  - **Tests Pest** (22 new): `Money`, `Slug`, `Locale`, `Url`, `AggregateRoot`, `Email` —
    27 tests / 51 assertions total

- Sprint 0 scaffolding:
  - Laravel 12 + Filament v4 + Inertia/Vue 3 + Tailwind v4 + Pinia
  - 10-service Docker Compose stack with healthchecks
  - DDD onion structure: 11 bounded contexts × 4 layers (Domain, Application, Infrastructure, Presentation)
  - Pest with `pest-plugin-arch` (9 architectural rules) + `pest-plugin-laravel`
  - Vitest + Playwright (chromium project)
  - GitHub Actions CI: 6 blocking jobs (lint, test-back, test-front, e2e, security, build)
  - Husky + lint-staged + commitlint (Conventional Commits)
  - Pint, Larastan + PHPStan level 8, ESLint flat config, Prettier
  - Sentry Laravel SDK + Telescope (local-only)
  - Custom Monolog `JsonFormatter` adding `service`, `bc`, `trace_id` fields
  - Filament admin panel at `/admin` with seeded admin from `.env`
  - 11 ADR (foundation set: Laravel 12, Filament v4, Postgres, modular monolith, 2× Redis,
    Cloudflare edge, DDD bounded contexts, hexagonal ports & adapters, Pest, Docker Compose,
    PHPStan level 8 → 9 plan)
  - C4 architecture diagrams (Mermaid) in `docs/architecture.md`
  - Onboarding guide in `docs/onboarding.md`
  - Makefile + Windows `make.cmd` wrapper

### Changed

- (none yet)

### Removed

- (none yet)

[Keep a Changelog]: https://keepachangelog.com/en/1.1.0/
[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
