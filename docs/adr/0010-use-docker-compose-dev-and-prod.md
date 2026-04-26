# ADR 0010 — Use Docker Compose for both dev and prod

## Status

Accepted — 2026-04-26

## Context

WebFactory's stack has 10 cooperating services (PHP-FPM app, Horizon, Scheduler, Reverb, Postgres,
2× Redis, Meilisearch, MinIO, nginx). On Windows (Williams' primary dev box) and on the Hetzner VPS
(production), we need a single source of truth for how they run together.

Mixing dev orchestration (e.g., Vagrant or Laravel Sail) with prod orchestration (e.g.,
Kubernetes / Nomad) doubles the operational surface for no MVP benefit.

## Decision

**Docker Compose** is the orchestrator in both dev (Windows + Docker Desktop + WSL2 backend) and
prod (Hetzner VPS, Linux). The same `docker-compose.yml` services are the deploy unit; production
adds an override file (`docker-compose.prod.yml`) for hardening (TLS, secrets, log drivers, no
exposed Postgres port, etc.).

Kubernetes / Nomad / ECS are explicitly **out of scope** for MVP. Migration is reconsidered if
multi-host scale becomes necessary (single Hetzner VPS handles MVP scale comfortably).

## Alternatives considered

- **Kubernetes** — over-engineered for a single-host VPS workload; weeks of YAML and operator
  glue for no observable benefit at this scale.
- **Laravel Sail in dev only** — fine in dev, but mismatches prod orchestration.
- **Bare PHP-FPM + systemd in prod** — possible, but loses dev/prod parity for the side-cars
  (Horizon, Reverb, MinIO).

## Consequences

- Positives:
  - Same compose file in dev and prod; muscle memory transfers.
  - Side-cars (Horizon, Scheduler, Reverb) are all explicit services with their own healthchecks.
  - Volume layout is identical in dev (named volumes) and prod (host paths, snapshotted).
- Negatives:
  - No automatic horizontal scaling; we are stuck with one host.
  - Compose is less mature than k8s for advanced rollout strategies.
- Neutral:
  - For MVP and even early scale, Docker Compose is enough — and we know when to graduate.

## Operational notes

- Dev: bind-mount the source tree, named volumes for `vendor/` and `node_modules/` to keep IO
  fast on Windows.
- Prod: deterministic image tags (no `latest`), ports exposed only via nginx + Cloudflare.
- Healthchecks defined for every service.

## References

- Spec 09 — Deployment
- `docker-compose.yml`
