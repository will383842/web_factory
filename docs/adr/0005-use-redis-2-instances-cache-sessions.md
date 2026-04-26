# ADR 0005 — Two Redis instances: cache and sessions

## Status

Accepted — 2026-04-26

## Context

WebFactory uses Redis for three workloads with conflicting policies:

1. **Cache / queues / locks**: hot, transient data. Memory pressure → evict LRU.
2. **User sessions**: long-lived state. Eviction here = users get logged out for free.
3. **Horizon queue payloads**: durable until processed. Eviction = lost jobs.

Mixing them in a single Redis with `allkeys-lru` puts sessions and queue jobs at risk. Mixing
them with `noeviction` blows up cache writes during memory pressure.

## Decision

Run **two Redis 7 instances** in their own containers:

- `wf-redis-cache` (port 6379): `--maxmemory 512mb --maxmemory-policy allkeys-lru` for cache,
  queues (Horizon), and atomic locks.
- `wf-redis-sessions` (port 6380): `--maxmemory 256mb --maxmemory-policy noeviction --appendonly yes`
  for user sessions (PHP session driver).

Laravel is configured to point each store to the right instance via dedicated env keys
(`REDIS_HOST` for cache, `REDIS_SESSION_HOST` for sessions).

## Alternatives considered

- **Single Redis with LRU** — leaks sessions. Unacceptable.
- **Single Redis with noeviction** — caches stop accepting writes when full. Operationally fragile.
- **Memcached for cache + Redis for sessions** — extra moving part for marginal gain.
- **Database sessions** — Postgres pressure for no benefit.

## Consequences

- Positives:
  - Predictable behavior under memory pressure.
  - Independent scaling and tuning.
  - Operationally explicit: each Redis has one purpose.
- Negatives:
  - +1 container, +1 port to expose.
  - +1 thing to monitor.
- Neutral:
  - Separating sessions from cache is a common pattern; tooling supports it.

## References

- Spec 01 — Stack & architecture
- Spec 09 — Deployment
