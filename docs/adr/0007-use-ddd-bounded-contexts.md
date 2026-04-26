# ADR 0007 — Use DDD bounded contexts (11 contexts canoniques)

## Status

Accepted — 2026-04-26

## Context

WebFactory's scope spans authentication, catalog, content, marketing, billing, communication,
search, analytics, AI features, and compliance. Without explicit boundaries, these concerns
quickly entangle: a "User" model that knows about subscriptions, content authorship, marketing
events, and audit logs becomes unmaintainable in 6 months.

Spec 27 fixes the boundaries up-front to keep the modular monolith truly modular.

## Decision

WebFactory has **exactly 11 bounded contexts** (Spec 27 §2):

1. **Identity** — auth, users, roles, permissions, 2FA, sessions
2. **Catalog** — categories, products, services
3. **Content** — pages, articles, FAQ, help, testimonials, news
4. **Marketing** — tracking, conversions, growth, A/B tests
5. **Billing** — subscriptions, invoices, payments, taxes
6. **Communication** — notifications, email, SMS, push, Telegram
7. **Search** — indexation, queries, recommendations
8. **Analytics** — events, KPIs, reporting
9. **Ai** — chatbot, embeddings, recommendations, copilot
10. **Compliance** — RGPD, audit, legal docs, consents
11. **Shared** — Money, Email, Url, Slug, Locale (kernel)

Adding a 12th BC requires an ADR. Renaming an existing one requires an ADR.

Each BC owns its `app/Domain/{BC}` (Entities, ValueObjects, Repositories interfaces, Services,
Events, Exceptions) and its `app/Application/{BC}` (Commands, Queries, DTOs).

## Alternatives considered

- **Free-form `app/Modules/...`** — too easy to slip; the next dev creates a 12th module by
  accident.
- **No explicit boundaries** — leads to a `User` model with 50 relations.
- **Different boundary cut** (e.g., merge Catalog+Content) — rejected after analysis: their
  lifecycle and editor personas are distinct.

## Consequences

- Positives:
  - Predictable directory structure.
  - Clear ownership of each cross-cutting concern.
  - Easier extraction to a service if ever needed (per ADR 0004).
- Negatives:
  - More directories to navigate.
  - Some logic crosses BC lines and requires Application-layer orchestration (Commands).
- Neutral:
  - Pest ArchTest enforces the boundaries; CI catches drift.

## References

- Spec 27 — Architecture & scalability §2 and §3
