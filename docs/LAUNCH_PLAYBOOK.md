# WebFactory — Launch Playbook (Sprint 25)

The exact runbook to flip a fresh WebFactory install from local to production.

## 0. Prerequisites

- Hetzner CX31 (or larger) Ubuntu 22.04 box
- Domain DNS pointing to the box (`webfactory.example.com` + wildcard `*.webfactory.example.com`)
- Cloudflare Pages or Vercel API token (for generated-site delivery)
- Anthropic API key (Sprint 19 swap)
- Stripe live keys (Sprint 16 swap)
- Postmark / Twilio / OneSignal credentials (Sprint 16 swap)

## 1. Provision

```bash
# On the host, after `apt update && apt upgrade -y`
git clone https://github.com/will383842/web_factory.git /opt/webfactory
cd /opt/webfactory
cp .env.production.example .env
# Edit .env — see "Secrets" section below
docker compose -f docker-compose.production.yml up -d
```

## 2. Secrets (env vars)

| Var | Source | Sprint |
|---|---|---|
| `APP_KEY` | `php artisan key:generate` | bootstrap |
| `DB_PASSWORD` | random 32-char | bootstrap |
| `WEBFACTORY_DEPLOY_DRIVER` | `hetzner` (or `cloudflare-pages`) | 16 (ADR 0042) |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | Stripe dashboard | 13.1 |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google Cloud OAuth | 13.2 |
| `ANTHROPIC_API_KEY` | Anthropic console | 19 |
| `POSTMARK_API_TOKEN` | Postmark | 13.4 |
| `TWILIO_SID` / `TWILIO_TOKEN` / `TWILIO_FROM` | Twilio | 13.4 |
| `INDEXNOW_KEY` | self-generated, hosted at `/{key}.txt` | 8/16 |
| `OTEL_EXPORTER_OTLP_ENDPOINT` | Grafana Tempo / Honeycomb | 18 |

## 3. Schema migration

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
```

## 4. Smoke tests

```bash
curl -sf https://webfactory.example.com/api/v1/health   # 200 + per-dep ok
curl -sf https://webfactory.example.com/                 # 200 + AeoAnswer block
curl -sf https://webfactory.example.com/manifest.webmanifest
```

## 5. First customer flow

1. Admin logs into `/admin`, creates a `Project` with a one-line idea.
2. Pipeline auto-runs steps 1–7 via the Horizon queue (see `wf-horizon`).
3. After ~3 min, the project's status becomes `deployed` and the `metadata.deployment.live_url` is browseable.
4. Admin reviews per-tenant content under `/admin/pages`, `/admin/articles`, `/admin/faqs`.
5. Send the live URL + login link to the customer.

## 6. Post-launch checks (24h)

- [ ] Telegram alert confirms first AutomationRequest received
- [ ] Stripe test payment succeeds (`stripe trigger payment_intent.succeeded`)
- [ ] Backup runs within 24h (Filament `/admin/backups`)
- [ ] IndexNow ping logged for the first deployed tenant
- [ ] Grafana panel shows `webfactory.app.requests_total` metric
- [ ] No 5xx in the Horizon failed queue

## 7. Rollback

```bash
docker compose exec app php artisan down --secret="emergency-rollback-token"
git checkout main~1
docker compose up -d --build
docker compose exec app php artisan up
```

## 8. Killswitch

If Anthropic billing or Stripe is compromised:

```bash
docker compose exec app php artisan tinker --execute="config(['features.ai_pipeline' => false]); cache()->forever('killswitch.ai', true);"
```

The pipeline jobs honor `cache()->get('killswitch.ai')` and short-circuit.
