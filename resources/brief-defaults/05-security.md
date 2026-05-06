## SECURITY — OWASP Top 10 2024 + headers durcis (injected by WebFactory)

### Headers HTTP — sur toute réponse

```
Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self), interest-cohort=()
Cross-Origin-Opener-Policy: same-origin
Cross-Origin-Embedder-Policy: require-corp
Cross-Origin-Resource-Policy: same-origin
```

CSP : aucun `'unsafe-inline'` sur `script-src` — utiliser nonces ou hashes pour les scripts inline obligatoires.

### Authentification

- **Argon2id** pour le hashing (jamais MD5/SHA1/bcrypt-rounds-too-low)
- Paramètres : memory_cost=65536, time_cost=4, threads=3 (réajuster selon CPU)
- Mot de passe minimum 12 caractères (NIST SP 800-63B), pas de complexity rules absurdes
- **2FA** TOTP obligatoire pour les comptes admin (RFC 6238)
- Recovery codes : 10 codes uniques, single-use, hashés en DB
- Magic links : token sha256, expire 15 min, single-use, IP+UA logged
- Session : `Secure`, `HttpOnly`, `SameSite=Lax`, rotation à chaque login
- Logout : invalidate côté serveur (jamais que côté client)
- Rate limit login : 5 tentatives / 5 min / IP avec Cloudflare ou Laravel rate limit
- Anti-enumeration : message identique pour "user inconnu" et "mauvais MDP"

### Autorisation

- Policies Laravel sur **chaque** modèle exposé
- Middleware role-based avec spatie/laravel-permission
- Tenant isolation : scope global Eloquent obligatoire si multi-tenant
- API : Sanctum personal access tokens, scoped (`user:read`, `posts:write`)
- Admin panel : middleware spécifique, ip whitelist optionnelle
- Pas de `Auth::user()` dans les services métier — passer l'user en paramètre

### Validation des inputs

- **Form Requests** Laravel pour toute validation HTTP
- Server-side **toujours**, client-side en bonus
- Whitelisting > blacklisting (déclarer ce qui est permis)
- Type strict : `int|string|email|url|date|enum`
- Length limits sur **chaque** string
- Regex pour les patterns connus (slug, phone, etc.)
- Sanitize HTML user-supplied via `htmlspecialchars()` ou DomPurify (frontend)
- Upload : whitelist mime-types **ET** check magic bytes (pas que l'extension)
- Upload : `max:5120` (5MB) par défaut, ajuster
- Upload : stockage hors webroot, servir via signed URL

### SQL injection

- **Requêtes paramétrées** uniquement — Eloquent ou query builder
- **Jamais** de string concat dans `DB::statement()` ou `whereRaw()`
- Si `whereRaw()` indispensable : binding params (`whereRaw('… = ?', [$value])`)
- Migrations : pas d'input user dans le schema
- Audit : Telescope en local, dashboard SQL slow queries en prod

### XSS prevention

- **Blade** échappe par défaut (`{{ $var }}`) — jamais utiliser `{!! !!}` sur user input
- React/Vue/Inertia : échappent par défaut, jamais `dangerouslySetInnerHTML` / `v-html` sur user input
- Markdown user-supplied : passer par DOMPurify ou Cogo (jamais de `{!! Str::markdown() !!}` sans sanitize)
- Cookies sensibles : `HttpOnly` (XSS ne peut pas les lire)

### CSRF

- Token Laravel sur **toutes** les routes web (POST/PUT/PATCH/DELETE)
- API : pas de CSRF (Sanctum tokens à la place)
- SameSite=Lax minimum (Strict pour les sessions admin)
- Origin / Referer header check sur les actions critiques

### Rate limiting

- Global : 60 req / min / IP
- Login : 5 / 5 min
- Reset password : 3 / 1 h
- API publique : 30 / 60 s, configurable
- API authentifiée : 1000 / heure
- Webhook entrants : pas de rate limit (mais signature HMAC obligatoire)
- Implémenté côté Cloudflare + Laravel `throttle` middleware (double layer)

### Secrets management

- `.env` jamais committé (`.gitignore`)
- `.env.example` documenté avec placeholders
- Production : env vars via secret manager (AWS Secrets Manager, HashiCorp Vault, ou docker secrets)
- Rotation régulière des API keys (90 j max pour les clés tierces)
- Pas de secret en logs (filtrer via `logging.context.processors`)

### Dependency security

- **Dependabot** actif (composer + npm + actions)
- `composer audit` en CI
- `npm audit --audit-level=moderate` en CI
- Pas de package non-mainteneur (vérifier last commit < 1 an)
- Lockfiles committés (`composer.lock`, `package-lock.json`)

### Webhooks entrants — signature obligatoire

```php
// Stripe — exemple
$signature = $request->header('Stripe-Signature');
$payload = $request->getContent();
$secret = config('services.stripe.webhook_secret');
$event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
// Si exception → 400, jamais 200
```

- Idempotency : utiliser l'event ID dans une table `webhook_events` UNIQUE
- Replay attack : check timestamp < 5 min
- Logger TOUS les webhooks reçus (succès et échec)

### Logs — règles RGPD

- **Pas de PII** en clair (email haché, IP tronquée à /24)
- **Pas de mot de passe** ni de token (filtrer)
- **Pas de Stripe payload complet** (filtrer cards, last4 OK)
- Rétention : 30 j hot, 1 an cold
- Centralisés (Better Stack / Datadog / Loki)

### Backups

- Quotidien minimum
- Encrypted at rest (AES-256)
- Off-site replication (S3 + Backblaze B2 minimum)
- Test de restauration mensuel (sinon = pas de backup)
- Borg ou pgBackRest pour Postgres

### Monitoring sécurité

- Alertes : login admin depuis nouvelle IP, 50+ 401 en 5 min, 5xx > 1 %
- Sentry : pas de PII dans les events (config `before_send`)
- Audit log : toute action admin tracée (`spatie/laravel-activitylog`)

### Anti-patterns

- ❌ `chmod 777` (chercher UID/GID corrects)
- ❌ Stocker JWT dans localStorage (XSS-vulnerable, utiliser cookie HttpOnly)
- ❌ `eval()` ou `exec()` sur input user — JAMAIS
- ❌ `unserialize()` sur input user — JAMAIS (RCE classique)
- ❌ `password = sha1($input)` — utiliser `Hash::make()` ou `password_hash()`
- ❌ HTTPS optionnel ou HSTS court (< 6 mois)
- ❌ Admin panel sur le même domaine que le public sans 2FA
- ❌ "Sécurité par l'obscurité" — ne pas y compter
