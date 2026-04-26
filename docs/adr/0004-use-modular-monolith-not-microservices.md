# ADR 0004 — Use a modular monolith, not microservices

## Status

Accepted — 2026-04-26

## Context

WebFactory is a single-team product with 11 bounded contexts that are tightly coupled at the
domain level (Identity ↔ Billing ↔ Catalog ↔ Content). At MVP scale (<10 RPS, <1M users), the
operational complexity of microservices buys nothing and creates friction:

- Distributed tracing, retries, idempotency at every call site
- Schema versioning across N services
- N CI/CD pipelines, N container images, N deploys per change
- Cross-service refactors require multi-PR ballet

## Decision

WebFactory ships as a **modular monolith**: one Laravel codebase, one container image (with
side-cars for Horizon / Scheduler / Reverb), one deploy unit. Modularity is enforced **inside
the codebase** via DDD bounded contexts and the layered architecture from Spec 27.

Microservices extraction is explicitly deferred until we measure a need (sustained scale issues
or team-size org change), not anticipated.

## Alternatives considered

- **Microservices from day one** — ruled out: see context.
- **Service-oriented monorepo** (multi-package, single repo) — adds packaging overhead without
  fixing the real coordination problems we'd hit.

## Consequences

- Positives:
  - One pipeline, one deploy, one trace.
  - Refactors stay atomic.
  - Onboarding cost is much lower.
- Negatives:
  - Architectural discipline is enforced by Pest ArchTest rather than network boundaries — if
    rules slip, contexts can leak into each other.
  - Vertical scale of a single deploy unit is the only knob until we extract.
- Neutral:
  - Communication between contexts uses Domain Events on async queues — same pattern as
    microservices, just in-process. This makes a future extraction easier if we ever need it.

## Mitigation

- Pest ArchTest enforces:
  - Domain layer is framework-free.
  - Domain does not depend on Infrastructure.
  - Eloquent is confined to Infrastructure.
  - Controllers do not import Eloquent models.
- Bounded context names are fixed (11) — Spec 27. New contexts require an ADR.

## References

- Spec 27 — Architecture & scalability
