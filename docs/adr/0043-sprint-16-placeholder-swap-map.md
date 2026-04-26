# ADR 0043 — Sprint-16 placeholder → real adapter swap map

**Status**: Accepted (Sprint 16)

**Context**

Sprints 7-15 ship every external integration behind a placeholder that
satisfies its application port without touching the network. This lets the
rest of the system (Filament, controllers, tests, listeners) be wired and
green before we acquire credentials. Sprint 16 (Hetzner deploy) is the
single point where every placeholder is swapped for a production adapter.

**Decision**

The swap is a binding-only change in
`App\Providers\DomainServiceProvider::register()`. No port signature changes,
no consumer changes. Each line below is the *only* edit Sprint 16 makes per
integration:

| Sprint | Port | Sprint 16 binds → |
|---|---|---|
| 6 | `GitHubRepositoryService` | `GitHubApiRepositoryService` (POST /user/repos via `github-php`) |
| 7 | `EmbeddingService` | `OpenAiEmbeddingService` (text-embedding-3-small, 1536-dim — schema migration to vector(1536)) |
| 8 | `IndexNowPingService` | `HttpIndexNowPingService` (POST api.indexnow.org) |
| 12 | `BackupService` | Cascade of `BorgBackupService` + `R2BackupService` + `B2BackupService` (Spec 11 5-level strategy) |
| 13.1 | `BillingGateway` | `StripeBillingGateway` (stripe-php SDK) |
| 13.1 | `BillingWebhookProcessor` | wraps in `SignatureVerifyingWebhookProcessor` (Stripe-Signature HMAC, then delegates to the idempotent intake) |
| 13.2 | `SsoProvider` × 5 | `SocialiteSsoProvider` per name (laravel/socialite — Google/Microsoft/Apple/Okta/GitHub) |
| 13.4 | `NotificationChannel` × 9 | `PostmarkAdapter`, `TwilioSmsAdapter`, `TwilioWhatsAppAdapter`, `WebPushAdapter`, `OneSignalAdapter`, `TelegramBotAdapter`, `SlackWebhookAdapter`, `DiscordWebhookAdapter`, `LaravelDatabaseInAppAdapter` |
| 16 | `DeploymentService` | `HetznerDeploymentService` (Ansible over SSH) |
| 19 | `IdeaAnalysisService` / `BlueprintGenerationService` / `DesignGenerationService` / `BriefBuilderService` / `ContentProductionService` | `Claude*Service` adapters (anthropic-sdk-php) |

**Consequences**

- The local container still runs entirely on placeholders → cheap dev loop.
- A staging environment can opt-in per integration by setting the env flag.
- Production env applies the full set; the swap is a 9-line PR + secrets.

**Risks + mitigations**

- A real adapter could change observable behaviour (e.g. Stripe webhook
  HMAC failures). Mitigation: each real adapter ships with its own
  integration test suite gated behind a `RUN_INTEGRATION_TESTS=1` env flag.
- Embedding dimensionality change (384 → 1536) requires a `pgvector` schema
  migration. Mitigation: `0050_resize_knowledge_chunks_embedding.php`
  ships in the same Sprint 16 PR.
