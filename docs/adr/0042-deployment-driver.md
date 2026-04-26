# ADR 0042 — Deployment driver pattern

**Status**: Accepted (Sprint 16)

**Context**

WebFactory ships generated projects to multiple deployment targets:
Hetzner (default), Cloudflare Pages (static profile), Vercel (Next.js
profile). Each target has different SSH/API mechanics. We need a stable
boundary so the rest of the pipeline (status transitions, IndexNow ping,
observability annotations) works identically regardless of target.

**Decision**

`App\Application\Catalog\Services\DeploymentService` is the stable port:

```php
public function deploy(Project $project): DeploymentResult;
public function provider(): string;
```

The Sprint-16 default impl is `PlaceholderDeploymentService` which returns a
synthetic `https://{slug}.webfactory.test` URL without touching infra. The
real adapters live in `App\Infrastructure\Pipeline\Deployments\`:

| Adapter | When |
|---|---|
| `HetznerDeploymentService` | Default. SSH + Ansible playbook over rsync. |
| `CloudflarePagesDeploymentService` | Static profiles. Pages API + git push. |
| `VercelDeploymentService` | Optional Next.js profile. |

The bound implementation is selected via the `WEBFACTORY_DEPLOY_DRIVER`
env var:

```env
WEBFACTORY_DEPLOY_DRIVER=hetzner   # or cloudflare-pages, vercel, placeholder
```

**Consequences**

- Pipeline step 7 (`DeployProjectJob`) is unchanged across targets.
- Adding a new target = one class implementing the port + one binding.
- Local + CI runs the placeholder so tests do not require infra credentials.
- The Sprint 8 IndexNow listener fires regardless of target — search-engine
  visibility is uniform.

**Alternatives considered**

- A driver registry similar to Sprint 13.4 notifications. Rejected: only one
  deployment target is active per WebFactory install, so a single binding is
  simpler than a name lookup.
