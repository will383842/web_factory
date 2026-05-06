## AUTH & ACCOUNT FLOWS (injected by WebFactory)

> Tout projet WebFactory avec utilisateurs doit implémenter **l'intégralité** des flows ci-dessous. Pas de "on fera la 2FA plus tard". Tout, dès le jour 1.

### Flow d'inscription (Register)

```
1. POST /auth/register
   ├─ Form validate (email RFC, password ≥ 12 chars, GDPR checkbox required)
   ├─ Anti-bot : Cloudflare Turnstile invisible (pas reCAPTCHA v2 obstructif)
   ├─ Anti-spam : check email vs liste throwaway (mailcheck)
   ├─ Create User (email_verified_at = null)
   ├─ Create personal Tenant si multi-tenant
   ├─ Assign role 'user' (Spatie Permission)
   ├─ Fire `Registered` event → SendEmailVerificationNotification
   ├─ Issue Sanctum personal access token
   └─ Return 201 { token, user }

2. Email reçu : "Vérifie ton email"
   ├─ Lien signed URL (expire 60 min)
   └─ Click → GET /auth/email/verify/{id}/{hash} → markAsVerified → redirect /onboarding/welcome

3. Si user clique pas sous 24h :
   └─ Reminder email auto (1× max, puis silence)
```

### Flow de connexion (Login)

```
1. POST /auth/login
   ├─ Form validate
   ├─ Anti-enumeration : message identique pour "user inconnu" vs "mauvais MDP"
   ├─ Rate limit : 5 tentatives / 5 min / IP (via throttle middleware + Cloudflare)
   ├─ Auth::attempt()
   ├─ Si 2FA enabled : issue challenge_token short-lived avec ability '2fa-pending'
   │   └─ Frontend redirige vers /auth/2fa/verify
   ├─ Sinon : issue Sanctum token + redirect /dashboard
   └─ Log event "auth.login" avec IP+UA dans audit_logs

2. Detection nouveau device/IP :
   └─ Email "Login from new device" automatique
```

### Magic Link (passwordless, mode passif)

```
1. POST /auth/magic-link/request { email }
   ├─ Si user existe : create MagicLinkToken (expires_at = now + 15 min, single-use)
   ├─ Envoi email avec lien signed
   ├─ Toujours répondre 200 (pas de leak)
   └─ Rate limit 3 / 1h / email

2. GET /auth/magic-link/consume?token=...
   ├─ Vérifier expiration + non-consumed
   ├─ markConsumed
   ├─ Issue Sanctum token
   └─ Redirect /dashboard
```

### 2FA TOTP (Time-based One-Time Password)

```
Setup :
1. POST /auth/2fa/enable
   ├─ Generate Google2FA secret
   ├─ Generate 10 recovery codes (single-use)
   ├─ Return { secret, qr_svg_base64, recovery_codes }
   └─ Frontend affiche QR + codes (avec bouton "Télécharger les codes")

2. POST /auth/2fa/confirm { code }
   ├─ Verify TOTP avec window=1 (30s)
   ├─ Set two_factor_confirmed_at
   └─ 2FA actif

Login flow :
1. POST /auth/login → returns challenge_token (ability '2fa-pending')
2. POST /auth/2fa/verify { challenge_token, code }
   ├─ Verify code (TOTP ou recovery)
   ├─ Si recovery : marquer ce code comme consumed
   ├─ Issue Sanctum token full
   └─ Redirect dashboard

Disable :
POST /auth/2fa/disable { password }
  ├─ Reconfirm password (anti-XSS attaquant qui aurait token)
  ├─ Clear two_factor_secret + recovery_codes
  └─ Email notification "2FA désactivée"
```

### SSO (Google, Apple, GitHub minimum)

```
1. GET /auth/sso/{provider}/redirect
   ├─ Generate state CSRF (40 chars stocké en session)
   ├─ Build authorization URL via Socialite
   └─ Redirect vers IdP

2. POST /auth/sso/{provider}/callback { code, state }
   ├─ Verify state CSRF
   ├─ Exchange code → access_token + user profile
   ├─ Cascade :
   │   1. Existing SsoIdentity (provider+provider_user_id) → return user + touch tokens
   │   2. Email match avec user existant → auto-link (création SsoIdentity)
   │   3. Sinon : create User (forceFill email_verified_at via SSO trust)
   └─ Issue Sanctum token + redirect /dashboard
```

Tokens SSO chiffrés en DB (cast `encrypted`).

### Password reset

```
1. POST /auth/forgot-password { email }
   ├─ Toujours 200 (anti-enumeration)
   ├─ Si user existe : create token + send email
   └─ Rate limit 3 / 1h

2. POST /auth/reset-password { token, email, password, password_confirmation }
   ├─ Verify token signed + not expired (60 min)
   ├─ Update password (Argon2id rounds adaptés)
   ├─ Invalidate all existing Sanctum tokens (sécurité)
   ├─ Email "Password changed" notification
   └─ Redirect /auth/login
```

### Account / Settings — pages obligatoires

| Route | Contenu |
|---|---|
| `/account/profile` | Avatar (drag-drop), nom, email (avec re-verification si changé), bio, locale, timezone, dateformat, currencyformat |
| `/account/security` | Password change form + 2FA setup/disable + Active sessions list (avec "Logout all devices") + Trusted devices |
| `/account/notifications` | Préférences par event_type × channel (matrice toggle). Transactionnels = lock à ON pour conformité |
| `/account/billing` | Current plan + invoices + payment method + upgrade/downgrade flow + cancel + tax info |
| `/account/api-tokens` | Create/list/revoke Sanctum tokens (pour devs) avec scopes granulaires |
| `/account/data` | RGPD : "Exporter mes données" (download JSON) + "Supprimer mon compte" (anonymisation + 30j grace period) |
| `/account/integrations` | Connect/disconnect : Google Calendar, Slack, etc. selon le brief |

### Onboarding flow (post-signup)

```
1. /onboarding/welcome
   ├─ "Bienvenue, {firstname} 👋"
   ├─ 3-step illustration : ce que va faire l'app pour eux
   └─ CTA "Commencer" → /onboarding/profile

2. /onboarding/profile
   ├─ Avatar upload + bio + role/persona dans le tenant
   └─ Skip ok, mais score d'activation -10 points

3. /onboarding/preferences
   ├─ Locale + timezone + notifications opt-in
   └─ Persisted dans NotificationPreference

4. /onboarding/first-action
   ├─ "Créons ton premier {entité}"
   └─ Tutorial inline (Tooltip + Highlights)

5. /dashboard
   └─ Empty state si pas d'action — "Voici comment commencer..."
```

Score d'activation 0-100 par utilisateur (cf. `App\Settings\OnboardingFlow` Sprint 13.3).

### Audit trail (obligatoire pour conformité)

Logger dans `audit_logs` :
- Login success / fail (email, IP, UA, timestamp)
- 2FA enabled / disabled / verified
- Password changed / reset
- Email changed
- Account deleted
- Token created / revoked
- SSO link / unlink

Rétention : 1 an minimum (RGPD-compliant si pas de PII en clair).

### Sessions actives — multi-device tracking

Page `/account/security` :
- Liste des sessions Sanctum actives
- Pour chaque : device + browser + IP géolocalisée + dernière activité
- Bouton "Logout this device" + "Logout all devices"
- Bouton "Trust this device" (skip 2FA pour 30j)

### Rate limiting

- Login : 5 / 5 min / IP
- Register : 3 / 1h / IP
- Password reset request : 3 / 1h / email
- Magic link request : 3 / 1h / email
- 2FA verify : 5 / 5 min / user

Implémenté côté Cloudflare (edge) + Laravel `throttle` middleware (origin) — double layer.

### Empty states (post-onboarding, dashboard vide)

> "Voici comment commencer..." + illustration + 3 actions suggérées avec icône + 1 phrase chacune.

**Jamais** un dashboard vide sans guide.

### À NE JAMAIS livrer sans

- ❌ Login sans rate limit
- ❌ Register sans email verification
- ❌ Password en MD5/SHA1/bcrypt < cost 10
- ❌ 2FA optionnel pour les comptes admin
- ❌ Reset password sans invalidation des tokens existants
- ❌ Account deletion sans confirmation double + grace period
- ❌ Onboarding skippé totalement (au moins 1 étape obligatoire)
- ❌ Login form sans `autocomplete="email"` + `autocomplete="current-password"`
