# Changelog

All notable changes to WebFactory are documented here. Format follows [Keep a Changelog],
and this project adheres to [Semantic Versioning].

## [Unreleased]

### Added

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
