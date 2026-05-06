## PRODUCTION-READINESS CHECKLIST (injected by WebFactory)

Tout projet doit cocher chacune des cases ci-dessous AVANT le 1er commit en production. Le `WorkspaceVerifier` de WebFactory checke automatiquement les éléments machine-vérifiables.

### Infrastructure & Deployment

- [ ] `docker-compose.yml` (dev) + `docker-compose.prod.yml` séparés
- [ ] Image Docker multi-stage build (build stage + runtime stage minimal)
- [ ] `.dockerignore` exclut `node_modules`, `vendor`, `.git`, `.env*`
- [ ] Healthcheck défini sur **chaque** service (`HEALTHCHECK CMD …`)
- [ ] Variables d'env injectées via `env_file` ou secret manager — **jamais** committées
- [ ] `.env.example` documente CHAQUE variable (1 commentaire minimum)
- [ ] HTTPS forcé (HSTS 1 an + preload + redirect 80→443)
- [ ] Reverse-proxy : Caddy ou Traefik (TLS auto Let's Encrypt)
- [ ] CDN devant : Cloudflare (cache assets, DDoS, GeoDNS)

### Configuration & Secrets

- [ ] `APP_KEY` généré (`php artisan key:generate`)
- [ ] `APP_DEBUG=false` en prod
- [ ] `APP_ENV=production`
- [ ] Secrets via env vars (jamais en dur)
- [ ] Logs JSON structurés (`monolog/json-formatter`)
- [ ] Niveau log `info` en prod, `debug` en local

### CI/CD

- [ ] `.github/workflows/ci.yml` : lint + tests + build sur chaque PR
- [ ] Jobs séparés : `lint`, `test-back`, `test-front`, `build`
- [ ] Échec d'un job bloque la PR (branch protection)
- [ ] Cache `composer` + `node_modules` pour vitesse
- [ ] Conventional Commits enforced (commitlint)
- [ ] `.github/dependabot.yml` actif (npm + composer + actions)

### Tests

- [ ] **Coverage minimum 70 %** sur backend (Pest)
- [ ] Tests d'architecture (`tests/Arch/`) avec règles d'isolation
- [ ] Tests d'API E2E (Pest Feature) — au minimum les endpoints publics
- [ ] Tests UI E2E (Playwright) — au minimum login + flux principal
- [ ] PHPStan **level 8** (objectif : level 9 à 6 mois)
- [ ] Pint clean (style PSR-12 + opinions Laravel)
- [ ] ESLint + Prettier sur frontend, `--max-warnings=0` en CI

### Observabilité

- [ ] Sentry SDK installé (DSN dans env)
- [ ] Sample rate traces : 0.1 en prod, 1.0 en local
- [ ] Healthcheck endpoint `/api/v1/health` (DB + cache + queue)
- [ ] Endpoint `/up` natif Laravel (probe Kubernetes/uptime)
- [ ] Logs centralisés (Better Stack, Datadog, ou simple Loki)
- [ ] Alertes : 5xx > 1 % sur 5 min, latence p95 > 1 s, queue stuck
- [ ] Dashboard Grafana ou Better Stack pour latence + erreurs

### Database

- [ ] Migrations versionnées et testées (un test Pest par migration critique)
- [ ] Index sur **chaque** colonne FK + colonnes de filtre/sort
- [ ] Soft deletes là où c'est métier-critique
- [ ] Backups : quotidien + rétention 30 j + test de restauration mensuel
- [ ] PITR (point-in-time recovery) si données critiques
- [ ] Pas de `SELECT *` — toujours colonnes explicites
- [ ] Eager loading : `with()` sur toute relation listée

### Queues & Jobs

- [ ] Queue dédiée par criticité (`high`, `default`, `low`)
- [ ] Tous les jobs : `tries=3`, `backoff` explicite, `timeout` raisonnable
- [ ] Failed jobs trackés (Horizon ou table `failed_jobs`)
- [ ] Job idempotent (replay safe) — testé
- [ ] Pas de logique métier dans les controllers — déléguer aux services/jobs

### Performance

- [ ] OPcache activé en prod (validate_timestamps=0)
- [ ] Redis pour cache + sessions + queue
- [ ] Cache HTTP : `Cache-Control` sur les routes statiques (immutable, 1y)
- [ ] Compression Brotli (Cloudflare) ou Gzip
- [ ] HTTP/2 + push critical resources
- [ ] Database connection pooling (PgBouncer) si > 100 RPS

### RGPD / Légal

- [ ] Cookie banner avec opt-in granulaire (analytics ≠ marketing ≠ preferences)
- [ ] Pages : Mentions légales, CGU, CGV, Privacy, Cookies — TOUTES
- [ ] Endpoint export RGPD (article 15)
- [ ] Endpoint suppression compte RGPD (article 17, anonymisation)
- [ ] Logs : pas de PII en clair (email haché si nécessaire)
- [ ] Si EU : DPA signé avec sous-traitants (Stripe, Sentry, etc.)

### Documentation

- [ ] `README.md` : setup en < 5 min (`make setup` et c'est parti)
- [ ] `CLAUDE.md` mis à jour (généré automatiquement par WebFactory à chaque pipeline pass)
- [ ] `docs/architecture.md` : diagrammes C4 mermaid
- [ ] `docs/adr/` : décisions architecturales numérotées
- [ ] `LAUNCH_PLAYBOOK.md` : checklist déploiement + rollback

### Anti-patterns à bannir

- ❌ `chmod 777` (chercher les vrais permissions UID/GID)
- ❌ Mots de passe en MD5 ou SHA1 (utiliser Argon2id)
- ❌ Secret dans le code source ou config Git-trackée
- ❌ `try/catch` qui swallow l'exception sans log
- ❌ Routes admin sans middleware d'auth
- ❌ N+1 queries (utiliser `with()`, debug avec Telescope/Clockwork)
- ❌ Cron qui ne log rien (`>> /var/log/app-cron.log 2>&1`)
- ❌ `setTimeout` PHP > 30 s sans queue (passer en job async)
