# ADR 0008 — Use Hexagonal (Ports & Adapters) for Domain isolation

## Status

Accepted — 2026-04-26

## Context

Inside each bounded context (ADR 0007), we need a strict rule: the **business logic** (Domain
layer) must be expressible without referring to Laravel, Eloquent, HTTP, queues, or any I/O. If
the Domain depends on Eloquent, then:

- Tests slow down (every test boots the framework).
- Refactoring the persistence layer becomes a multi-week project.
- Junior contributors put HTTP concerns inside the Domain "because it's easier".

## Decision

We apply the **Hexagonal (Ports & Adapters)** pattern inside each bounded context:

- **Ports** are interfaces in `app/Domain/{BC}/Repositories/` (and `Services/` when stateless
  collaboration is needed).
- **Adapters** are concrete implementations in `app/Infrastructure/...` — Eloquent for persistence,
  HTTP clients for external APIs, mailers, queues, etc.
- Domain **never** imports `Illuminate\*`, `Symfony\*`, `Filament\*`, or `App\Infrastructure\*`.
- Application services (`app/Application/{BC}/Commands` and `Queries`) orchestrate use cases and
  inject ports via the container.

Pest ArchTest enforces all four rules at every CI run.

## Alternatives considered

- **Active Record only** (Laravel default) — fast to write, painful to test and refactor.
- **Hexagonal with anemic Domain** — half-measure; the Application layer ends up holding all
  the logic.
- **CQRS-only** — useful but doesn't address the framework-leak problem; we apply CQRS in
  Application but keep the hexagonal isolation around Domain.

## Consequences

- Positives:
  - Domain tests boot in milliseconds (pure PHP).
  - Adapters are swappable (e.g., switch Eloquent → DBAL, swap a mail provider).
  - The architecture is explicit; new contributors read one diagram.
- Negatives:
  - More files (interface + impl) for each concept.
  - Slightly more boilerplate when wiring container bindings.
- Neutral:
  - The cost is paid once per concept; payback compounds across years.

## References

- Spec 27 — Architecture & scalability §3 (folder structure) and §4 (rules)
- Pest ArchTest rules in `tests/Arch/ArchitectureTest.php`
