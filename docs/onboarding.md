# Onboarding — WebFactory

> Day-1 checklist for any new developer joining WebFactory.

## Prerequisites (one-time)

| Tool | Min version | Notes |
|------|-------------|-------|
| Docker Desktop | latest | WSL2 backend on Windows |
| Git | 2.40+ | Set `core.symlinks=true` and `core.longpaths=true` on Windows |
| Node.js | 20+ | For local linting / Vite tooling outside containers |
| GitHub CLI (`gh`) | optional | For PR workflows |
| PHP / Composer locally | optional | Everything can be run inside the `wf-app` container |

## First setup (~10 min)

```bash
git clone <repo-url> webfactory-app
cd webfactory-app

# Local environment file (never committed)
cp .env.example .env
# Edit .env and set ADMIN_EMAIL + ADMIN_PASSWORD for the local Filament seeder

# One-shot provisioning: build images, install deps, migrate, seed
make setup
```

`make setup` does:

1. `docker compose build`
2. `docker compose up -d`
3. `composer install` inside `wf-app`
4. `npm install` inside `wf-app`
5. `php artisan key:generate` if `APP_KEY` is empty
6. `php artisan migrate --force`
7. `php artisan db:seed` (creates the Filament admin from `.env`)
8. `npm run build`

When it finishes, you have:

- `http://localhost/up` returning 200
- `http://localhost/admin/login` accepting `${ADMIN_EMAIL}` / `${ADMIN_PASSWORD}`
- All 10 containers reporting `healthy` in `docker compose ps`

## Daily commands

```bash
make up           # start the stack
make down         # stop the stack (data persists)
make fresh        # drop DB + re-seed (DESTRUCTIVE)
make test         # run pest + vitest
make lint         # pint + phpstan + eslint + prettier --check
make tinker       # artisan tinker shell inside wf-app
make horizon      # tail Horizon logs
make migrate      # run pending migrations
```

On Windows, use either `make <target>` from Git Bash, or `make.cmd <target>` from PowerShell.

## Repo layout

```
webfactory-app/
├── app/
│   ├── Domain/{Identity,Catalog,Content,Marketing,Billing,Communication,Search,Analytics,Ai,Compliance,Shared}/
│   ├── Application/{Identity,Catalog,...}/{Commands,Queries,DTOs}/
│   ├── Infrastructure/{Persistence/Eloquent/{Models,Repositories},Cache,Search,Storage,Mail,Telegram,Ai,External}/
│   ├── Filament/{Resources,Pages,Widgets}/
│   ├── Http/Controllers/{Api/V1,Web}/
│   ├── Providers/Filament/AdminPanelProvider.php
│   └── Support/
├── docker/
│   ├── php/{Dockerfile,php.ini,entrypoint.sh}
│   └── nginx/default.conf
├── docs/
│   ├── adr/                # 11 ADR (foundation set)
│   ├── architecture.md     # C4 diagrams (Mermaid)
│   └── onboarding.md       # this file
├── tests/
│   ├── Arch/               # 9 architectural rules (Pest ArchTest)
│   ├── Feature/            # HTTP, DB-touching tests
│   ├── Unit/               # pure unit tests
│   ├── js/                 # Vitest tests
│   └── Browser/            # Playwright e2e
├── resources/{css,js,views}/
├── .github/workflows/ci.yml
├── docker-compose.yml
├── Makefile + make.cmd
└── ...
```

## Architectural discipline (read this carefully)

The Domain layer is **framework-agnostic**. Concretely:

- **Never** `use Illuminate\...` from anywhere under `app/Domain/`.
- **Never** `extend Model` from anywhere under `app/Domain/`.
- **Repository interfaces** live in `app/Domain/{BC}/Repositories/`.
- **Repository implementations** live in `app/Infrastructure/Persistence/Eloquent/Repositories/`
  and are bound in a service provider.

If you violate these rules, the `ci/lint` job and the `tests/Arch/ArchitectureTest.php` rules
will fail — read the failure message, it points to the exact file.

## Branching & commits

- `main` — protected. Merges only via PR after Sprint 0 close.
- `dev` — integration branch. Push small, well-scoped features here.
- Feature branches: `feat/{bc}-{topic}`, `fix/{bc}-{topic}`.
- Conventional Commits enforced by `commitlint` (Husky `commit-msg` hook).

## Where to ask for help

- **Specs** (read-only): `C:\Users\willi\Documents\Projets\webfactory\` — 33 markdown files,
  exhaustive.
- **Decisions taken**: `docs/adr/` — read these before introducing a new pattern.
- **Past incidents / project memory**: SOS Expat memory in Claude Code (`MEMORY.md`).
