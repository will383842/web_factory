# ADR 0002 — Use Filament v4 as the admin/console framework

## Status

Accepted — 2026-04-26

## Context

WebFactory ships a "console" — a fully featured admin to drive the platform: customers, projects,
content pipelines, billing, analytics, design system, etc. We need a framework that gives us
resource CRUD, forms, tables, widgets, custom pages, granular permissions, and theming, without
hand-rolling every screen.

Spec 27 lists ~25 console modules (15 standard + 10 optional). Building those from scratch is a
multi-quarter cost.

## Decision

We adopt **Filament v4** (not v3) as the admin framework, mounted at `/admin` via the
`AdminPanelProvider` Panel API.

## Alternatives considered

- **Filament v3** — stable, large ecosystem, but v4 ships major DX improvements (Schema/View,
  performance, components) and is the active branch going forward. Choosing v3 would force a v4
  migration before we leave Sprint 5.
- **Laravel Nova** — paid, less customizable for our deeply nested console modules.
- **Custom Inertia/Vue admin** — maximum flexibility, but multiplies build effort by ~6x for what
  Filament gives us out of the box.

## Consequences

- Positives:
  - 25 modules can be assembled in days each rather than weeks.
  - Built-in auth, permissions, multi-tenancy primitives, file uploads, table builder.
  - Filament v4 schema API aligns with our intent to keep admin code declarative.
- Negatives:
  - Coupling between Filament and Eloquent — we must keep this boundary inside Infrastructure
    (see ADR 0007).
  - Some advanced UI cases will require custom Livewire components.
- Neutral:
  - Filament v4 is moving fast; we lock the resolved tag in `composer.lock` and track upgrades
    via Dependabot.

## Notes

- The exact Filament v4 tag resolved during Sprint 0 install is recorded in `composer.lock`.
- If `composer require filament/filament:"^4.0"` resolves a beta/rc, that's expected this early
  in v4 lifecycle and it is acceptable for the scaffolding phase.

## References

- Spec 01 — Stack & architecture
- Spec 30 — Plan de développement (modules console)
