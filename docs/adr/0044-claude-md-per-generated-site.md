# 0044 — Per-platform CLAUDE.md committed by the pipeline

- Status: Accepted
- Date: 2026-04-27
- Sprint: 26 (post-25 incremental)

## Context

WebFactory's 7-step pipeline produces a complete Laravel + Filament platform per customer idea, with its own GitHub repository (Sprint 6) and live deployment URL (Sprint 16). Once the platform is live, future maintenance — by humans or by Claude Code agents — needs context: stack, locales, content footprint, deployment URL, repo coordinates, conventions inherited from the factory.

A CLAUDE.md committed at the root of each generated site is the canonical Claude Code mechanism for delivering that context. Without it, an agent dropped into a generated repository must reverse-engineer the structure from scratch.

## Decision

After step 6 (`ContentProduced`), the pipeline emits a parallel branch that:

1. Renders a markdown file from the project's accumulated metadata (analysis, blueprint, design, brief, github, content, deployment) via a Blade template at `resources/views/generators/project-claude-md.blade.php`.
2. Commits the result as `CLAUDE.md` at the root of the project's GitHub repository through the existing `GitHubRepositoryService` port (extended with a new `commitFile()` method).
3. Persists commit metadata (SHA, path, html URL, byte count) under the project's `metadata.documentation` key for admin audit.

The generation runs **in parallel** with `DeployProjectJob` (both listen to `ContentProduced`). Ordering is irrelevant: deploy ships the runtime, the documentation step writes a static doc file. They do not share state and do not block each other.

## Consequences

### Positive

- Every generated platform ships with a CLAUDE.md tuned to its own configuration — no manual step.
- Future Claude Code sessions on a generated repo start with full context (stack, locales, content footprint, deploy URL, conventions inherited).
- The Blade template is the single source of truth for what gets generated, and is editable by humans and reviewable in PRs.
- The GitHub port (`GitHubRepositoryService::commitFile`) gains a generic capability that future pipeline steps can reuse (e.g., commit a `SECURITY.md`, `LICENSE`, or environment-specific README).
- Idempotent: re-running the pipeline overwrites CLAUDE.md with the same SHA when inputs are identical (deterministic mock).

### Negative

- One additional queueable job (`WriteProjectDocumentationJob`) per pipeline run. Negligible footprint — a few hundred bytes of metadata, one filesystem write under MinIO in dev, one PUT contents call in prod.
- The Blade template lives in the factory repo, not in each generated site — schema changes to `metadata.*` keys must keep the template's null-coalescing fallbacks intact, otherwise generated sites get a partial CLAUDE.md.

### Neutral

- Manual edits to a generated CLAUDE.md will be **overwritten** on the next pipeline pass. The template documents this with a pointer to a `CLAUDE.local.md` convention for site-specific overrides.

## Swap-map for Sprint 16+

The Sprint-6 `MockGitHubRepositoryService::commitFile()` writes to the local Laravel filesystem disk under `repos/<full_name>/CLAUDE.md` (visible in MinIO during dev). The future Octokit-style adapter swaps this with a `PUT /repos/{owner}/{repo}/contents/CLAUDE.md` call using the `GH_TOKEN` secret — same port signature, no caller-side change.

## Files

- `app/Application/Catalog/Services/GitHubRepositoryService.php` (port extended)
- `app/Application/Catalog/DTOs/GitHubCommitInfo.php` (new DTO)
- `app/Application/Catalog/Services/ProjectDocumentationGenerator.php` (new app service)
- `app/Infrastructure/Pipeline/MockGitHubRepositoryService.php` (adapter extended)
- `app/Infrastructure/Pipeline/Jobs/WriteProjectDocumentationJob.php` (new job)
- `app/Infrastructure/Pipeline/Listeners/WriteDocumentationOnContentProduced.php` (new listener)
- `app/Providers/DomainServiceProvider.php` (event listener wired)
- `resources/views/generators/project-claude-md.blade.php` (template)
- `tests/Feature/Pipeline/ProjectDocumentationTest.php` (coverage)
