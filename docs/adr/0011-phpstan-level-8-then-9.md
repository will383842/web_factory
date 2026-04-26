# ADR 0011 — PHPStan level 8 now, plan to reach level 9 before Sprint 4

## Status

Accepted — 2026-04-26

## Context

The Sprint 0 mission text mentioned **PHPStan level 9**. The Spec 01 stack reference mentions
**level 8**. Both are extremely strict. The difference matters in practice:

- **Level 8** — strict generics, array shapes, iterables, mixed types, return inference.
- **Level 9** — adds **`mixed` is forbidden** (every value must have a known declared type).

Starting at level 9 on a green-field project where Laravel and Filament both pass `mixed` through
container resolution and panel APIs would force us to wrap nearly every framework call site in
narrowing logic. That cost is real and adds little safety in scaffolding code that has no business
logic yet.

## Decision

- **Sprint 0 → end of Sprint 3**: PHPStan **level 8** in `phpstan.neon`.
- A baseline file (`phpstan-baseline.neon`) is generated at scaffolding time so CI starts green.
- **Before Sprint 4 starts** we lift the level to **9** in a dedicated PR, fix or baseline the
  remaining `mixed` callsites, and update this ADR (status → "Superseded by ADR 0012").

## Alternatives considered

- **Start at level 9** — high cognitive cost during scaffolding, low payoff.
- **Stay at level 8 forever** — leaves a known weak spot in the type discipline.
- **Skip levels and use Psalm** — different tool, similar tradeoffs; sticking to PHPStan keeps
  the toolchain coherent with Larastan.

## Consequences

- Positives:
  - CI green from day one without baselining noise.
  - Real type discipline arrives once Domain code starts to exist (Sprint 2+).
- Negatives:
  - Two PHPStan moves instead of one (level 8 now, level 9 later).
- Neutral:
  - Larastan extension is enabled at both levels; no migration cost beyond the bump itself.

## Action items

- [ ] Sprint 3 review: open a tracking issue "PHPStan: bump to level 9 before Sprint 4 starts".
- [ ] Once bumped, supersede this ADR with a follow-up explaining the remaining baseline (if any).

## References

- Spec 01 — Stack & architecture
- `phpstan.neon`, `phpstan-baseline.neon`
