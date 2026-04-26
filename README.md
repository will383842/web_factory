# WebFactory

Plate-forme de fabrication automatisée de sites web (Laravel 12 + Filament v4 + Inertia/Vue 3 +
PostgreSQL 16 + Meilisearch + Redis + Reverb).

## Démarrage rapide

```bash
git clone <repo-url> webfactory-app
cd webfactory-app
cp .env.example .env
# Renseigner ADMIN_EMAIL et ADMIN_PASSWORD dans .env
make setup
```

→ `http://localhost/up` répond 200 ; `http://localhost/admin/login` accepte les credentials du `.env`.

## Commandes utiles

| Commande | Effet |
|----------|-------|
| `make up` / `make down` | Démarre / arrête la stack |
| `make test` | Pest + Vitest |
| `make lint` | Pint + PHPStan + ESLint + Prettier |
| `make migrate` | `php artisan migrate` |
| `make seed` | `php artisan db:seed` |
| `make tinker` | Shell `php artisan tinker` |
| `make horizon` | Logs Horizon |
| `make fresh` | Drop DB + re-seed (destructif) |

Sur Windows, `make.cmd` est un wrapper qui forwarde vers le `Makefile` via Docker — toutes les
cibles fonctionnent depuis PowerShell, cmd, ou Git Bash.

## Architecture

Modular monolith DDD avec 11 bounded contexts (Spec 27).

```
app/
├── Domain/                 # Logique métier pure (zéro Illuminate)
├── Application/            # Use cases (Commands / Queries / DTOs)
├── Infrastructure/         # Eloquent, HTTP clients, Storage, Search, ...
├── Filament/               # Resources / Pages / Widgets
├── Http/Controllers/       # API + Web
└── ...
```

Voir `docs/architecture.md` (diagrammes C4 Mermaid) et `docs/adr/` (11 ADR fondateurs).

## Stack

- **Backend**: Laravel 12 · PHP 8.3 · Filament v4 · Livewire 3 · Horizon 5 · Reverb 1 · Sanctum ·
  Telescope · Sentry SDK
- **Frontend**: Vite · TypeScript strict · Inertia + Vue 3 · Tailwind v4 · Pinia
- **Data**: PostgreSQL 16 · Redis 7 ×2 · Meilisearch 1.x · MinIO (S3)
- **Tests**: Pest (+ ArchTest) · Vitest · Playwright
- **Qualité**: Pint · Larastan + PHPStan L8 · ESLint · Prettier · commitlint · Gitleaks · Husky

## Tests architecturaux

`tests/Arch/ArchitectureTest.php` enforce 9 règles en CI:

1. `App\Domain` n'utilise pas `Illuminate`
2. `App\Domain` n'utilise pas `Symfony`
3. `App\Domain` n'utilise pas `App\Infrastructure`
4. `App\Domain` n'utilise pas `Filament`
5. Les Repositories en Domain sont des interfaces
6. Eloquent confiné à `App\Infrastructure\Persistence\Eloquent` (+ `App\Models` legacy)
7. Les Controllers HTTP n'importent pas Eloquent
8. Pest preset `php()` (best practices)
9. Pest preset `security()`

## Onboarding

Voir `docs/onboarding.md`.

## Contribution

Branches : `feat/{bc}-{topic}`, `fix/{bc}-{topic}`. Conventional Commits requis.

## Liens

- Spec complète (read-only) : `C:\Users\willi\Documents\Projets\webfactory\`
- ADR : `docs/adr/`
- Architecture : `docs/architecture.md`
- CI : `.github/workflows/ci.yml`

## Licence

Propriétaire — tous droits réservés (à définir).
