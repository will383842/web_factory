# Changelog

All notable changes to WebFactory are documented here. Format follows [Keep a Changelog],
and this project adheres to [Semantic Versioning].

## [Unreleased]

### Added

- Sprint 10 — Filament Content modules (CRUD admin Page/Article/FAQ) :
  - **PageResource** : navigation group "Content", icon document-text, form 3 sections (Identity / Type & status / Content + KeyValue meta tags + textarea content_blocks JSON), table avec status/type/locale badges + 3 filtres
  - **ArticleResource** : icon newspaper, form 4 sections (Identity / Content avec body live afterStateUpdated → auto word_count + reading_time / SEO TagsInput keywords + is_pillar toggle / Stats computed read-only), table avec is_pillar boolean column + 3 filtres incl. TernaryFilter pillar
  - **FaqResource** : icon question-mark-circle, form 3 sections (Q&A / Categorization / Engagement read-only), table searchable question + filtres
  - 9 routes Filament : `/admin/{pages,articles,faqs}` × {index, create, edit}
  - **Tests Pest** (5 nouveaux, +138 total → **138 / 356 assertions**) : admin reaches Pages/Articles/FAQs index + Page create form, plain user role 403 forbidden
  - Quality : PHPStan No errors, Pint **253 files PASS**

- Sprint 9 — Multi-tenancy + Multilingue + AudienceContext :
  - **AudienceContext DTO** : locale + countryCode + currency + primaryBank + popularCities + localCompetitors + dateFormat + phoneFormat
  - **AudienceContextService** : 13 contextes pré-câblés (fr-FR/BNP/EUR, fr-CA/Desjardins/CAD, en-US/Chase/USD, en-GB/Barclays/GBP, en-IN/SBI/INR, es-ES/Santander/EUR, es-MX/Banamex/MXN, ar-MA/Attijariwafa/MAD, ar-SA/Al Rajhi/SAR, hi-IN/HDFC/INR, pt-BR/Itaú/BRL, de-DE/Deutsche Bank/EUR, zh-CN/工商银行/CNY) + fallback cascade : exact match → same-language any-region → en-US default
  - **TenantContext middleware** : extrait `project_id` depuis (1) `X-Project-Id` header, (2) route binding `{project}`, (3) user owned project (fallback) ; tag `app('tenant.project_id')` + `Log::withContext` pour audit ; appliqué automatiquement à toutes les routes API via `bootstrap/app.php`
  - **Tests Pest** (12 nouveaux, +133 total → **133 / 351 assertions**) :
    - AudienceContext : 8 tests (fr-FR BNP, fr-CA Desjardins, ar-MA Attijariwafa, en-IN SBI, fallback xx-YY → en-US, fallback "fr" → fr-FR, supportedLocales, toArray roundtrip)
    - TenantContext middleware : 4 tests (header X-Project-Id, fallback user owned, anonymous null, header > user precedence)

- Sprint 8 — SEO/AEO Engine (Marketing BC) :
  - **DTOs** : `JsonLdSchema`, `HreflangAlternate`, `SitemapEntry`, `InternalLinkSuggestion`
  - **JsonLdGenerator** : 5 schémas schema.org (`WebSite` avec `SearchAction`, `Organization`, `Article`, `FAQPage` AEO-essential, `BreadcrumbList`)
  - **HreflangBuilder** : multi-locale + auto `x-default` fallback, render HTML link tags
  - **SitemapBuilder** : `sitemap.xml` valide (`<urlset>` + `xhtml:link` alternates inline)
  - **IndexNowPingService** port + `LogIndexNowPingService` (Sprint 16 swap → real HTTP adapter Bing/Yandex/Naver/Seznam)
  - **InternalLinkSuggester** : utilise `KnowledgeBaseSearchService` (pgvector cosine) pour suggérer top N liens internes pertinents par cosine similarity, exclut l'article source, génère anchor hint
  - **AeoOptimizer** : score 0-100 sur 6 axes (Q-headings, short answer paragraphs, TL;DR, FAQ section, lists, definitions) + suggestions actionables
  - **DomainServiceProvider** : binding `KnowledgeBaseSearchService` → `PgVectorKnowledgeBase` + `IndexNowPingService` → `LogIndexNowPingService` ; `PgVectorKnowledgeBase` implémente désormais le port `KnowledgeBaseSearchService`
  - **Tests Pest** (14 nouveaux, +121 total → **121 / 314 assertions**) :
    - JsonLdGenerator : 6 tests (WebSite SearchAction, Organization, Article, FAQPage AEO, BreadcrumbList, JSON serialization no escaped slashes)
    - HreflangBuilder + SitemapBuilder : 4 tests (alternates + x-default, HTML render, empty input, sitemap.xml structure complète)
    - InternalLinkSuggester : 1 test (KB-powered, exclude self)
    - AeoOptimizer : 3 tests (full optimized ≥80, wall-of-text <40, partial credit)

- Sprint 7 — Content Engine + multi-tenant + pgvector (KB foundation) :
  - **Postgres image** : switch `postgres:16-alpine` → `pgvector/pgvector:pg16` (extension `vector` v0.8.2)
  - **Migrations multi-tenant** :
    - `pages` (FK project_id, slug+locale unique, type, status, content_blocks JSON, meta_tags JSON)
    - `articles` (FK project_id, body, excerpt, seo_keywords, is_pillar, word_count, reading_time, quality_score)
    - `faqs` (FK project_id, question, answer, category, is_featured, view/helpful counts)
    - `knowledge_chunks` (FK project_id, source_type, content, **`embedding vector(384)`** + HNSW cosine index)
  - **Domain Content** enrichi : `Page` (status workflow + projectId + content_blocks), nouveau `Article`, nouveau `Faq`, enum `ContentStatus`
  - **Events Content** : `ArticlePublished` (nouveau), `FaqAnswered` (nouveau), `PagePublished` (existant)
  - **Eloquent models** : `Page`, `Article`, `Faq`, `KnowledgeChunk` (multi-tenant via FK project_id)
  - **Application services** :
    - port `EmbeddingService` (impl `HeuristicEmbeddingService` 384-dim hash-based BoW L2-normalized)
    - port `KnowledgeBaseSearchService` + `KnowledgeChunkSearchResult` DTO
  - **Infrastructure** : `PgVectorKnowledgeBase` ingest + cosine search via pgvector `<=>` operator avec scope multi-tenant
  - **Listener** `IngestPublishedContentToKnowledgeBase` : auto-ingestion sur `PagePublished` + `ArticlePublished`
  - **Tests Pest** (8 nouveaux, +107 total → **107 / 269 assertions**) :
    - Unit Embedding : 384-dim, L2-normalized, non-zero on empty, semantic similarity > unrelated
    - Feature KB : ingest + 384-dim stored, top match correct sur "ciel meteo Paris", **multi-tenant isolation (no cross-tenant leak)**, auto-ingest article on publish event
  - DomainServiceProvider : binding EmbeddingService + 2 listeners (PagePublished, ArticlePublished)
  - **Note** : Filament resources (PageResource, ArticleResource, FaqResource) déférés en Sprint 10/11 (Spec 30)

- Sprint 6.5 — Public B2C Auth API (REST, Sanctum, no web views) :
  - **Packages** : `pragmarx/google2fa` v9 + `bacon/bacon-qr-code` v3
  - **Migration** `add_b2c_auth_columns_to_users` : `two_factor_secret` (text encrypted), `two_factor_recovery_codes` (text encrypted:array), `two_factor_confirmed_at` (timestamp)
  - **Migration** `magic_link_tokens` (FK user, token unique, expires_at, consumed_at)
  - **Model** `User` : `MustVerifyEmail` interface + `hasTwoFactorEnabled()` helper + casts encrypted/datetime + Hidden 2FA fields
  - **Model** `MagicLinkToken` (BelongsTo user, isExpired/isConsumed/isUsable)
  - **14 endpoints REST `/api/v1/auth/*`** :
    - `POST /register` — crée user (rôle 'user') + dispatch Registered + Sanctum token (201)
    - `POST /login` — credentials → token (ou `challenge_token` si 2FA activé)
    - `POST /logout` — revoke current token (204)
    - `GET /me` — user authentifié + roles + 2fa flag
    - `POST /forgot-password` — email reset (no leak email enum)
    - `POST /reset-password` — valide token + set password + revoke tokens
    - `GET /email/verify/{id}/{hash}` — signed URL verification
    - `POST /email/resend` — resend verification email
    - `POST /2fa/enable` — secret + QR SVG base64 + 8 recovery codes
    - `POST /2fa/confirm` — valide TOTP, sets confirmed_at
    - `POST /2fa/verify` — exchange challenge_token + TOTP → Sanctum token
    - `POST /2fa/disable` — password reconfirm required
    - `POST /magic-link/request` — issue 60-min signed token, log it (Mail in prod)
    - `GET /magic-link/consume?token=` — single-use → Sanctum token
  - **Tests Pest** (15 nouveaux, +99 total → **99 / 255 assertions**) : register OK + weak password 422 + dup email 422, login OK + invalid 422, logout revoke, /me, forgot-password, magic-link request+consume + already-consumed + expired, 2FA enable/confirm/disable + login challenge_token

### Fixed

- Sprint 6 hash sha1 → sha256 (Pest security preset rule)

- Sprint 6 — Pipeline orchestrator étapes 4-5 :
  - **Domain Events Catalog** : `BriefBuilt`, `BriefScored`, `GitHubRepositoryCreated`
  - **Exception** : `BriefScoreTooLowException` (gate ≥85)
  - **Application DTOs** : `BriefBundle` (files map + checksum), `BriefScore` (score + gaps + strengths + threshold const), `GitHubRepoInfo`
  - **Application service ports** : `BriefBuilderService`, `BriefScorerService`, `GitHubRepositoryService`
  - **Infrastructure adapters Sprint 6 (heuristic / mock)** :
    - `HeuristicBriefBuilderService` — produit ≥35 fichiers (README, blueprint.json, design tokens, page briefs, mockups, .env.example, configs templates, 10 instructions docs/)
    - `HeuristicBriefScorerService` — score 6 axes (présence requis 40pts, page briefs 15pts, mockups 15pts, README body 10pts, virality≥60 10pts, value≥50 10pts) avec gaps/strengths
    - `MockGitHubRepositoryService` — coordonnées `webfactory-org/{slug}` déterministes
  - **Horizon Jobs** chainés (3 retries + 30/60s backoff) :
    - `BuildBriefJob` step 4a : transition Designing→Building, Storage::disk('s3')->put projects/{id}/brief.json, dispatch BriefBuilt, chain ScoreBriefJob
    - `ScoreBriefJob` step 4b : score le brief, dispatch BriefScored ; **throws BriefScoreTooLowException si <85** (gate), sinon chain InitGitHubRepoJob
    - `InitGitHubRepoJob` step 5 : crée le repo GitHub (mock) + dispatch GitHubRepositoryCreated
  - **Listener** `StartBuildOnDesignGenerated` chaîne auto Sprint 5→6 sur DesignGenerated
  - **DomainServiceProvider** : 3 nouveaux bindings + 1 listener (boot)
  - **Tests Pest** (7 nouveaux, +84 total → **84 / 208 assertions**) :
    - Sprint 6 BriefBuilder ≥35 files + checksum sha1, Scorer accepts/rejects, Mock GitHub coords, **full pipeline 1-5 sync → status=building + 6 metadata keys**, BuildBriefJob queued on DesignGenerated, BriefBuilt+BriefScored+GitHubRepositoryCreated chain dispatch
  - Sprint-5 pipeline test renommé pour ne plus exiger status=designing (pipeline va jusqu'à building désormais)


- Sprint 5 — Pipeline orchestrator (étapes 1-3) :
  - **Domain Events** Catalog : `IdeaAnalyzed`, `BlueprintGenerated`, `DesignGenerated`
  - **Application DTOs** : `IdeaAnalysisResult` (virality+value+clarifications+strengths+weaknesses),
    `Blueprint` (pages+journeys+kpis), `DesignSystem` (tokens+mockups)
  - **Application service ports** : `IdeaAnalysisService`, `BlueprintGenerationService`, `DesignGenerationService`
  - **Infrastructure adapters Sprint 5 (heuristics, mock IA)** :
    - `HeuristicIdeaAnalysisService` — scoring déterministe (longueur, keywords viraux, locale bonus)
    - `HeuristicBlueprintGenerationService` — 10 pages standard + 3 journeys + 5 KPIs
    - `HeuristicDesignGenerationService` — token set indigo/slate + 8 mockups HTML
    - Sprint 19 swappera ces adapters pour les versions Claude API
  - **Horizon Jobs** chainés (3 retries + 30s backoff) :
    - `AnalyzeProjectIdeaJob` — step 1 : transition Draft→Analyzing, scoring, dispatch IdeaAnalyzed, chain step 2
    - `GenerateBlueprintJob` — step 2 : transition →Blueprinting, génération blueprint, chain step 3
    - `GenerateDesignJob` — step 3 : transition →Designing, génération design system + mockups
  - **Listener** `StartPipelineOnProjectCreated` auto-déclenche AnalyzeProjectIdeaJob sur ProjectCreated
  - **DomainServiceProvider** wires les 3 service ports + le listener via `Dispatcher::listen()` dans `boot()`
  - **Tests Pest** (12 nouveaux, +77 total → **77 / 181 assertions**) :
    - Unit : 4 tests `HeuristicIdeaAnalysisService`, 2 `HeuristicBlueprintGenerationService`, 2 `HeuristicDesignGenerationService`
    - Feature : 4 tests `PipelineChainTest` (Bus::fake → queued, sync queue → designing+metadata, chain steps, IdeaAnalyzed event)

- Sprint 4 — Catalog BC complet:
  - **Domain**: `Catalog\Project` aggregate root (renommage de Product Sprint 1):
    fields slug+name+description+status+locale+primaryDomain+viralityScore+valueScore+ownerId+metadata
  - `ValueObjects\ProjectStatus` enum (7 états : Draft → Analyzing → Blueprinting → Designing → Building → Deployed, + Archived terminal)
  - `Events\ProjectCreated`, `Events\ProjectStatusChanged`
  - `Exceptions\InvalidProjectStatusTransitionException`
  - `Contracts\ProjectRepositoryInterface` (findById/findBySlug/save/delete/findByOwner/findByStatus)
  - **Workflow** : `submit()`, `transitionTo()` (linéaire forward-only), `archive()` (depuis n'importe quel non-terminal), `score()` (clamp 0-100)
  - **Persistence** :
    - Migration `projects` (slug unique, status indexé, FK owner_id → users, soft-delete, json metadata)
    - Eloquent `App\Models\Project` (HasFactory + SoftDeletes + AsArrayObject metadata + BelongsTo owner)
    - `Mappers\ProjectMapper` (Domain ↔ Eloquent)
    - `Repositories\EloquentProjectRepository` (binding wired in `DomainServiceProvider`)
  - **Application** : `Commands\CreateProjectCommand` (DTO readonly), `Handlers\CreateProjectHandler` (insert + dispatch ProjectCreated)
  - **Filament** : `Resources/Projects/ProjectResource` avec **wizard 5 étapes** (Idea / Audience / Stack / Branding / Review),
    table avec status badges + filtre + soft-delete
  - **API REST** :
    - `laravel/sanctum` API installé (table `personal_access_tokens`)
    - `User` model implémente `HasApiTokens`
    - `routes/api.php` réécrit avec préfixe `/api/v1` + `auth:sanctum`
    - `Http/Controllers/Api/V1/ProjectController` (index paginé scoped owner, show, store, destroy ; admins voient tout)
    - `Http/Resources/Api/V1/ProjectResource` (transformation JSON)
    - `Http/Requests/Api/V1/StoreProjectRequest` (validation slug regex unique + locale BCP-47)
  - **Tests Pest** (19 nouveaux, +65 total → **65 / 132 assertions**) :
    - `Unit/Domain/Catalog/ProjectTest` (7) : starts in draft + records, 5-step pipeline, refus skip/backwards, archive, clamp scores, rehydrate sans events
    - `Feature/Catalog/EloquentProjectRepositoryTest` (5) : findById/findBySlug, save mutate, findByOwner+status ordered desc
    - `Feature/Catalog/CreateProjectHandlerTest` (1) : flow e2e + ProjectCreated dispatch
    - `Feature/Api/V1/ProjectApiTest` (6) : 401 unauthenticated, scoping owner, admin sees all, POST 201, validation 422, 403 forbidden cross-owner
  - PHPStan ignore patterns ajoutés pour BelongsTo template covariance (Larastan open issue)

- Sprint 3 — Console Filament base:
  - **`spatie/laravel-settings` v3.7** + **`filament/spatie-laravel-settings-plugin` v4.11** installed
  - `App\Settings\GeneralSettings` (siteName, siteTagline, supportEmail, defaultLocale, maintenanceMode)
    — persisted in the `settings` table (group `general`), JSON payload, cached
  - Settings migration `2026_04_26_160000_create_general_settings.php` with default values
  - **Filament admin enhancements** in `AdminPanelProvider`:
    - `->profile()` — user profile page at `/admin/profile`
    - `->darkMode()` — dark/light theme toggle persisted in user prefs
    - `->sidebarCollapsibleOnDesktop()` — better UX on wide screens
  - **Filament admin pages**:
    - `Pages/ManageGeneralSettings` — 3-section form (Branding / Contact & locale / Operations)
      mounted at `/admin/manage-general-settings`
  - **Filament resources**:
    - `Resources/Users/UserResource` upgraded — sectioned form (Identity / Authentication / Authorization),
      role assignment via CheckboxList, password hash on dehydrate (optional on edit), role badges in table,
      filter by role, sort by id desc
    - `Resources/Roles/RoleResource` (new) — CRUD over `Spatie\Permission\Models\Role`,
      permission CheckboxList, permissions_count + users_count columns, sort by name
  - **Tests Pest** (9 new, +46 total → 46 / 85 assertions):
    - `Console/GeneralSettingsTest` (3): default values, save/reload roundtrip, group()
    - `Console/AdminPanelRoutesTest` (6): /admin redirect to login, /admin/login 200, admin role
      reaches /admin/users + /admin/roles + /admin/manage-general-settings, "user" role 403s

- Sprint 2 — Identity BC implementation (pragmatic core):
  - **Eloquent adapter** for Identity:
    - `app/Infrastructure/Persistence/Eloquent/Mappers/UserMapper` — Domain ↔ Eloquent translation
    - `app/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepository` implements `UserRepositoryInterface`
    - Wired in `DomainServiceProvider`
  - **Spatie Permission** (v7.3.0) integrated:
    - Migrations published + run (5 tables: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions)
    - `RolePermissionSeeder` seeds the canonical 3-role taxonomy (admin/editor/user) + 16 baseline permissions
    - `App\Models\User` has `HasRoles` trait; `canAccessPanel()` requires admin or editor
    - `AdminUserSeeder` automatically assigns the `admin` role to the seeded admin
  - **Application use case** `RegisterUserHandler` + `RegisterUserCommand` DTO
    (creates Eloquent record + builds Domain aggregate + dispatches `UserRegistered`)
  - **Filament Admin Resource** for User: CRUD + role assignment via `php artisan make:filament-resource User`
  - **Tests Pest** (10 new, +27 total → 37/72):
    - `tests/Feature/Identity/EloquentUserRepositoryTest` — find/save/delete via repo
    - `tests/Feature/Identity/RegisterUserHandlerTest` — command flow + password hashing + event dispatch
    - `tests/Feature/Identity/RolePermissionTest` — 3-role taxonomy, admin all-perms, editor subset, role assignment
  - **PHPStan** ignore patterns refined for Pest dynamic test patterns ($this->prop in beforeEach, $not magic prop, factory()->create() nullable result narrowing)

### Deferred to Sprint 2.5

- 2FA TOTP (`spatie/laravel-qrcode` + `spomky-labs/otphp`)
- Magic links (signed URL flow)
- Custom password reset (Laravel default still works via Filament)
- API REST `/api/v1/auth/*` endpoints (Sanctum tokens)

- Sprint 1 — Architecture squelette:
  - **Shared kernel** (`app/Domain/Shared`):
    - `Events/DomainEvent` (abstract), `Contracts/EventDispatcher` (interface)
    - `Entities/AggregateRoot` (event recording with `recordEvent`/`flushEvents`/`pendingEvents`)
    - `Exceptions/DomainException` (abstract base + `errorCode()`)
    - Value Objects: `Money` + `Currency`, `Url`, `Locale` (with city tag), `Slug` (ASCII)
    - 5 dedicated `Exceptions/` (InvalidCurrency/MoneyCurrencyMismatch/InvalidUrl/InvalidLocale/InvalidSlug)
  - **11 Bounded Contexts** scaffolded with the 4-file pattern (Entity / Event / Exception / RepositoryInterface):
    - `Identity` (User aggregate, Email VO, UserRegistered event)
    - `Catalog` (Product aggregate, ProductCreated event — reference example)
    - `Content` (Page aggregate, PagePublished event)
    - `Marketing` (Conversion aggregate, ConversionTracked event)
    - `Billing` (Subscription aggregate, SubscriptionCreated event)
    - `Communication` (Notification aggregate, NotificationSent event)
    - `Search` (SearchIndex aggregate, IndexUpdated event)
    - `Analytics` (MetricEvent aggregate, MetricsRecorded event)
    - `Ai` (KnowledgeChunk aggregate w/ vector embedding, EmbeddingGenerated event)
    - `Compliance` (AuditLog aggregate, ConsentRecorded event)
  - **Infrastructure** adapter `LaravelEventDispatcher` (forwards to Laravel's `Dispatcher`)
  - **ServiceProvider** `DomainServiceProvider` registered in `bootstrap/providers.php` (binds `EventDispatcher` → `LaravelEventDispatcher`)
  - **ArchTest** extended from 9 → 14 rules: every Domain event extends `DomainEvent`,
    every Identity/Catalog exception extends `DomainException`, Shared+Identity VOs are
    `final`, no Guzzle/Symfony HttpClient in Domain, Application is also infrastructure-free
  - **Tests Pest** (22 new): `Money`, `Slug`, `Locale`, `Url`, `AggregateRoot`, `Email` —
    27 tests / 51 assertions total

- Sprint 0 scaffolding:
  - Laravel 12 + Filament v4 + Inertia/Vue 3 + Tailwind v4 + Pinia
  - 10-service Docker Compose stack with healthchecks
  - DDD onion structure: 11 bounded contexts × 4 layers (Domain, Application, Infrastructure, Presentation)
  - Pest with `pest-plugin-arch` (9 architectural rules) + `pest-plugin-laravel`
  - Vitest + Playwright (chromium project)
  - GitHub Actions CI: 6 blocking jobs (lint, test-back, test-front, e2e, security, build)
  - Husky + lint-staged + commitlint (Conventional Commits)
  - Pint, Larastan + PHPStan level 8, ESLint flat config, Prettier
  - Sentry Laravel SDK + Telescope (local-only)
  - Custom Monolog `JsonFormatter` adding `service`, `bc`, `trace_id` fields
  - Filament admin panel at `/admin` with seeded admin from `.env`
  - 11 ADR (foundation set: Laravel 12, Filament v4, Postgres, modular monolith, 2× Redis,
    Cloudflare edge, DDD bounded contexts, hexagonal ports & adapters, Pest, Docker Compose,
    PHPStan level 8 → 9 plan)
  - C4 architecture diagrams (Mermaid) in `docs/architecture.md`
  - Onboarding guide in `docs/onboarding.md`
  - Makefile + Windows `make.cmd` wrapper

### Changed

- (none yet)

### Removed

- (none yet)

[Keep a Changelog]: https://keepachangelog.com/en/1.1.0/
[Semantic Versioning]: https://semver.org/spec/v2.0.0.html
