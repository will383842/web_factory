# ADR 0003 — Use PostgreSQL 16 over MySQL/MariaDB

## Status

Accepted — 2026-04-26

## Context

WebFactory needs a relational database that supports:

- Strong typing with native JSON/JSONB (audit logs, config blobs, content payloads)
- Full-text search fallback when Meilisearch is offline
- Robust transaction semantics (MVCC, SERIALIZABLE)
- Window functions, CTEs, partial indexes (analytics queries on the events table)
- Native ENUM and array types
- Mature replication for the eventual read-replica scenario

## Decision

We adopt **PostgreSQL 16** as the unique relational database for WebFactory in dev, CI, and
production.

## Alternatives considered

- **MySQL 8 / MariaDB 11** — perfectly capable for OLTP, but JSON support is weaker (no JSONB-like
  GIN indexes), and analytics queries planned for Sprint 14+ benefit much more from Postgres.
- **SQLite** — fine for local prototypes, not production; would diverge dev from prod.

## Consequences

- Positives:
  - JSONB + GIN indexes for content blobs and audit trails.
  - CTEs, window functions, lateral joins → simpler analytics SQL.
  - Stronger transaction and isolation guarantees.
- Negatives:
  - Slightly steeper operational ramp than MySQL for newcomers.
  - Some Laravel features (full-text indexes) take a slightly different syntax.
- Neutral:
  - All Laravel ORM features are first-class on Postgres.

## Operational notes

- Container image: `postgres:16-alpine` in dev/CI.
- Production: managed Postgres on Hetzner VPS (or upgrade to a managed service later).
- Backups: pg_basebackup nightly, retention 30 days (defined in Spec 09).

## References

- Spec 01 — Stack & architecture
- Spec 09 — Deployment
