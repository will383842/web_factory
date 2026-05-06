# CLAUDE.md — WebFactory

> Contexte projet pour Claude Code. À lire en premier à chaque session.

## Pitch

**WebFactory** est une usine logicielle qui génère des plateformes web prêtes pour la production (Laravel + Filament) à partir d'une seule idée, en **7 étapes de pipeline asynchrone**. C'est un **modular monolith DDD** avec 11 bounded contexts, pas un microservice. **Pas de Firebase ici** (le projet voisin SOS Expat utilise Firebase, pas celui-ci).

## Stack technique (réelle, vérifiée)

| Couche | Stack |
|---|---|
| **PHP / Framework** | PHP 8.3 (container `wf-app` tourne 8.4) · **Laravel 13.6** (`composer.json: laravel/framework ^13.0` — le README mentionne "Laravel 12" par erreur, c'est 13) |
| **Admin** | **Filament v4** + Livewire 3 |
| **Async / queues** | Horizon 5 (Redis), Reverb 1 (WebSockets), Scheduler |
| **Auth** | Sanctum (tokens API), Spatie Permission, Google2FA, MagicLink, SSO placeholder (Google/MS/Apple/Okta/GitHub) |
| **Frontend public** | **Blade** (`resources/views/public/home.blade.php`) — Vue 3 / Inertia / Pinia sont **installés mais inutilisés** (`resources/js/Pages/Welcome.vue` est une démo non montée, build Vite sort `app.js` à 0 kB) |
| **Data** | **PostgreSQL 16 + pgvector** (image `pgvector/pgvector:pg16`), **Redis 7 ×2** (cache + sessions, séparés), **Meilisearch 1.6**, **MinIO** (S3-compatible) |
| **Observabilité** | Telescope (local), Sentry (DSN à fournir en prod) |
| **Tests** | **Pest 4** + Pest plugins arch + laravel · Vitest · Playwright |
| **Qualité** | Pint, Larastan + **PHPStan level 8**, ESLint, Prettier, commitlint, Gitleaks, Husky |
| **Build** | Vite 8 + Tailwind v4, TypeScript strict |
| **Infra dev** | Docker Compose, 10 containers (`wf-app`, `wf-horizon`, `wf-scheduler`, `wf-reverb`, `wf-nginx`, `wf-postgres`, `wf-redis-cache`, `wf-redis-sessions`, `wf-meili`, `wf-minio`) |

## Quick start

```bash
cp .env.example .env                    # Mets ADMIN_EMAIL / ADMIN_PASSWORD
make setup                              # build + up + composer + npm + key + migrate + seed + build
# Puis :
#   http://localhost/up           → 200 (healthcheck)
#   http://localhost/api/v1/health → app/db/redis ok
#   http://localhost/admin/login  → Filament (admin@webfactory.local par défaut)
```

Sur Windows : `make.cmd <target>` depuis PowerShell/cmd, ou `make <target>` depuis Git Bash.

## Architecture

### Layout `app/` (DDD strict, isolation enforcée par tests d'archi)

```
app/
├── Domain/                # Métier pur — INTERDIT d'importer Illuminate, Symfony, Filament, App\Infrastructure
├── Application/           # Use cases : Commands, Queries, Handlers, DTOs, Services (ports)
├── Infrastructure/        # Eloquent, HTTP clients, Storage, Pipeline jobs, adapters
├── Filament/              # Resources (20) + Pages (3) — admin
├── Http/Controllers/      # API V1 + Web — INTERDIT d'importer Eloquent
├── Models/                # Eloquent, autorisé seulement ici + Infrastructure/Persistence/Eloquent
├── Providers/             # AppServiceProvider, DomainServiceProvider, AdminPanelProvider, TelescopeServiceProvider
└── Settings/, Support/    # Spatie laravel-settings + helpers
```

### 11 Bounded Contexts (`app/Domain/`)

`Ai`, `Analytics`, `Billing`, `Catalog`, `Communication`, `Compliance`, `Content`, `Identity`, `Marketing`, `Search`, `Shared`.

### Pipeline 7-step (création de plateforme)

Chaîne événementielle, jobs asynchrones queueables :

```
ProjectCreated
  → AnalyzeProjectIdeaJob          → metadata.analysis
  → GenerateBlueprintJob           → metadata.blueprint
  → GenerateDesignJob              → metadata.design
DesignGenerated
  → BuildBriefJob + ScoreBriefJob  → metadata.brief, brief_score
  → InitGitHubRepoJob              → metadata.github
GitHubRepositoryCreated
  → ProduceContentJob              → Pages × locales, Articles pillar, FAQs canonical
ContentProduced (parallel branches)
  ├→ DeployProjectJob              → metadata.deployment, status=deployed, IndexNow ping
  └→ WriteProjectDocumentationJob  → metadata.documentation, commits CLAUDE.md to repo
```

Ports `App\Application\Catalog\Services\*` ; adapters Sprint-15/16 sont des `Heuristic*` / `Placeholder*`. Swap réels (Claude AI, GitHub API, Hetzner/CF Pages, Stripe SDK, Socialite, Postmark, etc.) documentés dans `docs/adr/0043-sprint-16-placeholder-swap-map.md`.

### Per-platform CLAUDE.md (Sprint 26)

Chaque site généré reçoit son propre `CLAUDE.md` à la racine de son repo, rendu depuis `resources/views/generators/project-claude-md.blade.php` à partir des metadata accumulées par le pipeline. Voir ADR `docs/adr/0044-claude-md-per-generated-site.md`. Le port `GitHubRepositoryService::commitFile()` est l'API générique réutilisable pour pousser d'autres fichiers de doc (SECURITY.md, LICENSE, …) dans la même veine.

## Fichiers et endroits clés

| Sujet | Chemin |
|---|---|
| **Pipeline orchestration** | `app/Infrastructure/Pipeline/Jobs/*.php` (8 jobs) + `Listeners/*.php` (4 chaînages) |
| **Bindings DI (placeholders → swap)** | `app/Providers/DomainServiceProvider.php` |
| **Routes API publiques** | `routes/api.php` (25 routes : `/api/v1/...`) |
| **Routes web (Blade + manifest + login fallback)** | `routes/web.php` |
| **Filament admin** | `app/Filament/Resources/` (20) + `Pages/` (3) |
| **Migrations** | `database/migrations/` (35) |
| **Seeders obligatoires** | `RolePermissionSeeder` (3 rôles : admin/editor/user) + `AdminUserSeeder` |
| **Sécurité headers** | `app/Http/Middleware/SecurityHeaders.php` (registered global dans `bootstrap/app.php`) |
| **Tenant / multi-projet** | `app/Http/Middleware/TenantContext.php` |
| **PWA** | `public/manifest.webmanifest`, `public/sw.js` |
| **Health** | `GET /api/v1/health` → `HealthController` (app + db + redis) ; `GET /up` natif Laravel |
| **RGPD** | `GET /api/v1/me/export` (Art.15) + `DELETE /api/v1/me` (Art.17) — `GdprController` |
| **Stripe webhook** | `POST /api/v1/billing/webhooks/stripe` — idempotency garantie par `IdempotentBillingWebhookProcessor` |
| **CI** | `.github/workflows/ci.yml` (Pint + PHPStan + ESLint + Prettier + Pest + Vitest) |
| **ADR** | `docs/adr/0001..0043` (13 ADRs) |
| **Spec read-only** | `C:\Users\willi\Documents\Projets\webfactory\` (33 fichiers spec hors repo) |

## Conventions de code

- **PHP** : `declare(strict_types=1);`, classes `final` quand possible, type-hints partout.
- **Filament** : 1 Resource par modèle exposé, navigation groupée par bounded context.
- **DI** : ports en `App\Application\*\Services` ou `App\Domain\*\Contracts`, adapters en `App\Infrastructure\*`. Bindings centralisés dans `DomainServiceProvider`.
- **Events** : `App\Domain\<BC>\Events\*` étend `App\Domain\Shared\Events\DomainEvent`.
- **Jobs** : implémentent `ShouldQueue`, `tries=3`, `backoff` explicite.
- **Tests d'archi** (`tests/Arch/ArchitectureTest.php` — 9 règles enforced) :
  1. `App\Domain` n'importe pas `Illuminate`
  2. `App\Domain` n'importe pas `Symfony`
  3. `App\Domain` n'importe pas `App\Infrastructure`
  4. `App\Domain` n'importe pas `Filament`
  5. Repositories en Domain = interfaces
  6. Eloquent confiné à `App\Infrastructure\Persistence\Eloquent` + `App\Models`
  7. Controllers HTTP n'importent pas Eloquent
  8. Pest preset `php()`
  9. Pest preset `security()`
- **Branches** : `feat/{bc}-{topic}`, `fix/{bc}-{topic}`. **Conventional Commits requis** (commitlint enforced).
- **Migrations** : nommage horodaté `YYYY_MM_DD_HHMMSS_create_<table>_table.php`.

## Testing & Quality

| Commande | Effet |
|---|---|
| `make test` | Pest + Vitest |
| `make test-back` | Pest only — référence : **238 tests / 664 assertions ≈ 4 min** |
| `make test-arch` | Tests d'architecture seuls |
| `make lint` | Pint --test + PHPStan L8 + ESLint + Prettier --check |
| `make fix` | Pint + Prettier --write |

**Important** : Pest utilise SQLite in-memory (`phpunit.xml` force `DB_CONNECTION=sqlite,DB_DATABASE=:memory:`) — les tests **ne touchent pas** Postgres. Les tests `RefreshDatabase` n'affectent que la DB de test.

## Déploiement

- **Pipeline driver** = `WEBFACTORY_DEPLOY_DRIVER` (env). Sprint 16 défaut : `PlaceholderDeploymentService`. ADR 0042 documente les swaps Hetzner / Cloudflare Pages / Vercel.
- **Playbook complet** : `docs/LAUNCH_PLAYBOOK.md` (provisioning Hetzner, secrets, migrations, smoke tests, rollback, AI killswitch).
- **Checklist** : `docs/PRODUCTION_CHECKLIST.md` (11 sections — pipeline / BCs / quality / Filament / public / sécurité / RGPD / observability / backups / PWA / Sprint-16 swap-map).
- **CI** : tout PR vers `main`/`dev` lance lint + PHPStan + tests. Pas de déploiement auto fourni : à configurer en sortie de CI selon `WEBFACTORY_DEPLOY_DRIVER`.

## Sécurité

Headers OWASP par défaut sur **toutes** les réponses (middleware `SecurityHeaders`) :
`X-Content-Type-Options nosniff`, `X-Frame-Options DENY`, HSTS 1y, CSP self+Bunny Fonts, `Referrer-Policy strict-origin-when-cross-origin`, `Permissions-Policy camera=()/microphone=()/geolocation=()`. Les sites générés par le pipeline overrident leur CSP via leur brief.

`auth.verification.expire` config par défaut (60 min). Tokens Sanctum personnels. 2FA TOTP via `pragmarx/google2fa`.

## ⚠️ Pièges connus à ne pas reproduire

1. **`User implements MustVerifyEmail`** : la route signée par défaut s'appelle `verification.verify`. Notre route est `api.v1.auth.verification.verify`. **`AppServiceProvider::boot()` configure `VerifyEmail::createUrlUsing()` pour pointer dessus** — ne pas supprimer ce hook, sinon toute inscription B2C crashe avec `RouteNotFoundException`. Les tests Pest `Notification::fake()` masquent ce bug, donc le crash ne sort qu'en E2E réel.
2. **`composer setup` (script `composer.json`) lance `db:seed`** : sans seed, `RolePermissionSeeder` n'a pas créé les rôles `admin/editor/user` → premier `assignRole('user')` crashe.
3. **Manifest PWA** : nginx ne connaît pas `.webmanifest` par défaut. La config `docker/nginx/default.conf` déclare le MIME type `application/manifest+json` ; ne pas l'enlever, sinon le navigateur rejette le manifeste.
4. **X-Frame-Options** : nginx ne pose **aucun** header de sécurité contradictoire. Source de vérité = middleware `SecurityHeaders` (DENY). Ne pas réintroduire `add_header X-Frame-Options SAMEORIGIN` dans nginx.
5. **Pas d'endpoint UI Vue/Inertia** malgré la stack installée : si tu construis un dashboard tenant SPA, c'est greenfield. Sinon, les deps Vue sont candidates au nettoyage.
6. **Tests parallèles** : `php artisan test --parallel=N` n'est pas supporté ici (paratest installé sans alias). Utiliser `php artisan test --compact` ou `vendor/bin/pest --parallel` directement.

## Quality Defaults Pack — Sprint 28 (préambules + verifier)

Sprint 28 ajoute deux couches de qualité automatiques au workflow Sprint 27 :

### 1. Injection automatique de **10 préambules** dans CHAQUE brief uploadé

À l'upload d'un brief ZIP, WebFactory **prepend** automatiquement 10 préambules markdown au `CLAUDE.md` du développeur **avant** que Claude Code ne le lise. Ces préambules définissent le DNA non-négociable de tout site WebFactory : qualité technique + complétude fonctionnelle.

#### Quality Pack (Sprint 28)

| Préambule | Contenu |
|---|---|
| `01-design.md` | Tailwind v4, shadcn/ui, Radix, Framer Motion, dark mode, tokens HSL, container queries, Core Web Vitals 2026 |
| `02-production.md` | Docker multi-stage, HSTS preload, CI/CD, tests 70 % coverage, PHPStan L8, Sentry, backups, queues, RGPD |
| `03-accessibility.md` | WCAG 2.2 AA min, semantic HTML, keyboard nav, ARIA, contrasts, prefers-reduced-motion, i18n |
| `04-seo-aeo.md` | Core Web Vitals 2026, JSON-LD, AEO (Generative Search), robots.txt pour GPTBot/ClaudeBot/etc., IndexNow |
| `05-security.md` | OWASP Top 10 2024, CSP strict, Argon2id, 2FA, rate limiting, validation, webhooks signés |

#### Completeness Pack (Sprint 30) — DNA fonctionnel

| Préambule | Contenu |
|---|---|
| `06-pages-layout.md` | Header sticky + Footer 4 colonnes + **25+ pages publiques imposées** (Home, About, Pricing, Contact, FAQ, Blog, Mentions légales, CGU, CGV, Privacy, Cookies, 404, 500, Sitemap, Robots, Manifest, RSS, etc.) + pages B2C |
| `07-components.md` | Catalogue de **30+ composants** : Hero, Features, PricingTable, BlogCard, **CookieBanner GDPR**, **CommandMenu Cmd+K**, NewsletterForm, EmptyState, LoadingSkeleton, etc. |
| `08-content-engine.md` | **6 articles seed minimum** au lancement, structure article (TL;DR + TOC + AEO), tables `articles`/`categories`/`tags`/`authors`, RSS, sitemap blog, calendrier 1/semaine |
| `09-auth-account.md` | Register / Login / Magic Link / **2FA TOTP** / SSO (Google + Apple + GitHub min) / password reset / **9 pages account** / onboarding 5 étapes / audit trail / sessions multi-device |
| `10-comms.md` | **15 templates emails transactionnels** (welcome, password_reset, login_anomaly, payment_failed, etc.) + 6 marketing + **9 channels notifications** (in_app/email/sms/whatsapp/push×2/telegram/slack/discord) + matrice opt-in |

**Source** : `resources/brief-defaults/0X-*.md` (versionnés Git, ~56 KB total).
**Édition live** : `/admin/manage-brief-defaults` — **10 sections collapsibles** dans Filament.
**Master switch** : toggle `injectionEnabled` pour désactiver temporairement.
**Idempotency** : marker HTML évite le double-injection sur re-import.
**Ordre** : quality pack → completeness pack → brief original (LLMs weighting earlier instructions).

### 2. Workspace verifier après génération

Bouton **"Verify workspace"** dans la page Project — scan filesystem pur, sans subprocess :
- **50+ checks pondérés** : CLAUDE.md, README, .env.example, docker-compose, composer.json + structure Laravel + package.json + tsconfig + Tailwind + .github/workflows + dependabot
- **Sprint 30 — completeness checks** : header_view, footer_view, manifest, sw.js, robots.txt, sitemap, rss, page_about/contact/pricing/faq, blog_index/show, legal_mentions/terms/privacy/cookies, error_404/500, auth_login/register, filament_admin, email templates (layout/welcome/password_reset)
- Multi-pattern : chaque check accepte plusieurs paths conventionnels (`resources/views/components/header.blade.php` OU `resources/views/layouts/partials/header.blade.php`)
- Stack-aware : détecte automatiquement `backend/`, `frontend/` ou root
- Score 0-100, threshold `isReady() >= 80 && no failures`
- Rapport visuel : passed (vert) / failed (rouge) / warnings (ambre) en `<details>` collapsibles
- Persistance dans `metadata.workspace_verification` pour audit ultérieur

### Implémentation côté Domain

Aucune modification du Domain métier. Les composants vivent dans :
- `App\Application\Catalog\Services\BriefDefaultsInjector` (port + adapter Settings)
- `App\Application\Catalog\Services\WorkspaceVerifier` (filesystem-only, pas de port)
- `App\Application\Catalog\DTOs\WorkspaceVerificationReport` (DTO)
- `App\Settings\BriefDefaultsSettings` (Spatie laravel-settings)
- `App\Filament\Pages\ManageBriefDefaults` (Settings page)

## Workflow concret — Sprint 27 (brief upload → Claude Code → push)

C'est le mode de production réel. Mis en place le 2026-04-27 pour fonctionner avec l'abonnement Claude Pro/Max du développeur (zéro coût marginal API Anthropic) :

1. **Tu prépares un brief** : un dossier avec `CLAUDE.md` à la racine + `docs/` (specs détaillées). Tu zippes.
2. **Tu uploads le ZIP** sur `/admin/upload-brief` (Filament). Tu fournis : slug, nom, locale primaire, URL du repo GitHub que tu as créé manuellement (vide).
3. **WebFactory** :
   - Crée le `Project` aggregate
   - Extrait le ZIP dans `C:/Users/willi/Documents/Projets/{slug}/` (host filesystem, monté dans wf-app via `/host/projects`)
   - Parse `CLAUDE.md` (titre, sections H2, stack détectée, URL prod)
   - Persiste sous `metadata.brief` + `metadata.github`
   - Te redirige vers la page edit du projet, qui affiche le path host + commande `cd ... && claude` à copier
4. **Toi** (workflow VS Code, recommandé) :
   - Click "Open in VS Code" dans la page Project (déclenche `vscode://file/...`)
   - VS Code s'ouvre sur `C:\Users\willi\Documents\Projets\{slug}` avec `CLAUDE.md` visible
   - <kbd>Ctrl+Shift+P</kbd> → "Claude Code: Start" (extension Claude Code)
   - Claude Code lit `CLAUDE.md` + `docs/`, génère le projet sous tes yeux dans l'IDE
   - Tu reviews, ajustes, valides
4.bis. **Workflow alternatif (terminal pur)** : `cd C:\Users\willi\Documents\Projets\{slug} && claude` — résultat identique.
5. **Toi (à la fin)** : retour dans Filament → page projet → bouton **"Push to GitHub"** (Phase 2 livrée Sprint 29). Si `WEBFACTORY_GH_TOKEN` est dans `.env`, push 100 % auto. Sinon, le repo est préparé localement (init+commit+remote) et la commande exacte de push est affichée à copier-coller dans ton terminal.

Le port `BriefImporter` gère :
- Slugs ASCII safes (regex identique à `Slug` VO)
- Refuse l'écrasement d'un workspace existant (protège les générations en cours)
- Normalise les ZIP `Compress-Archive` Windows (qui mettent `\` au lieu de `/`)
- Auto-flatten du wrapper single-folder
- Path-traversal guard
- Parser CLAUDE.md léger (regex H1/H2, mots-clés stack)

**Important** : la stack Sprint 26 (per-platform CLAUDE.md auto-généré post-pipeline) **n'est PAS utilisée** dans ce workflow. Elle reste viable pour un futur passage en mode SaaS automatique avec API Anthropic. Aujourd'hui le CLAUDE.md vient du brief — c'est plus précis et c'est toi qui le contrôles.

## Note pour les plateformes générées

**Implémenté Sprint 26 (2026-04-27)** : chaque plateforme générée reçoit automatiquement son propre `CLAUDE.md` à la racine de son repo. La chaîne est :

```
ContentProduced → WriteDocumentationOnContentProduced (listener parallèle)
                → WriteProjectDocumentationJob (queueable)
                → ProjectDocumentationGenerator::generateClaudeMd($project)  [Blade render]
                → GitHubRepositoryService::commitFile($repo, 'CLAUDE.md', $md, $msg)
                → metadata.documentation = {sha, path, html_url, message, bytes}
```

Manuels overrides : éditer **`CLAUDE.local.md`** (jamais réécrit par le pipeline). Le `CLAUDE.md` est régénéré à chaque pipeline pass.

Voir `docs/adr/0044-claude-md-per-generated-site.md` pour le détail du design et le swap-map vers l'adapter Octokit réel.

---

*Dernière mise à jour : 2026-04-27. Audit production-readiness + Sprint 26 (per-platform CLAUDE.md). 244 tests / 699 assertions PASS, PHPStan L8 0 errors, Pint 425 files PASS, pipeline 7-step E2E réel `status=deployed` + `metadata.documentation` populé en environnement Docker. 4 fixes appliqués pendant l'audit (VerifyEmail route, composer setup db:seed, manifest MIME, headers dédoublonnés). Voir CHANGELOG.md.*
