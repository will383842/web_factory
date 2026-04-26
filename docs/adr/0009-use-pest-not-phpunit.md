# ADR 0009 — Use Pest as the test framework (not PHPUnit directly)

## Status

Accepted — 2026-04-26

## Context

Laravel ships with PHPUnit by default. Pest is a thin layer on top that adds:

- Expressive `it()` / `test()` / `expect()` syntax — closer to Jest/Vitest, easier to read.
- Higher-order tests, datasets, parallel execution baked in.
- A first-class **arch testing** plugin (`pestphp/pest-plugin-arch`) — critical for enforcing
  ADR 0007 / ADR 0008 (DDD onion isolation) directly from CI.

## Decision

We adopt **Pest** as the unique testing framework for backend (PHP) tests, with these plugins:

- `pestphp/pest-plugin-laravel` — Laravel-specific helpers (HTTP, DB, queues, events).
- `pestphp/pest-plugin-arch` — architectural rules (Domain isolation, CQRS naming, Eloquent
  containment).

PHPUnit remains transitively present (Pest is built on top of it) but we never write `extends
TestCase` style by default; Pest's functional style is preferred.

Frontend tests use **Vitest** (Sprint 0) — not in scope of this ADR.

## Alternatives considered

- **PHPUnit alone** — works, but loses arch testing convenience and developer ergonomics.
- **Codeception** — too heavy for our profile.

## Consequences

- Positives:
  - Less boilerplate per test.
  - Architectural drift is caught by `arch()` rules running in CI on every PR.
  - Datasets + higher-order tests reduce duplicate test code in domain-heavy contexts.
- Negatives:
  - One more abstraction layer to learn.
  - Stack traces sometimes route through Pest internals before reaching test code.
- Neutral:
  - PHPUnit remains under the hood — interop with PHPUnit-only tooling stays possible.

## References

- Spec 20 — Tests strategy
- `tests/Arch/ArchitectureTest.php` — 9 architectural rules enforced from Sprint 0
