## EMAILS TRANSACTIONNELS & NOTIFICATIONS (injected by WebFactory)

> Tout projet WebFactory doit shipper avec une **matrice de notifications complète** : transactionnels obligatoires, marketing opt-in, multi-channel. Aucun email "send and pray" — toujours templates Markdown, dark/light variants, responsive, tracking optionnel mais propre.

### Templates emails obligatoires (transactionnels — RGPD-compliant)

Ces emails sont **légalement requis** ou **opérationnellement critiques**. Ils contournent l'opt-out marketing (basis légale = exécution du contrat).

| Template | Trigger | Subject (FR) | Champs dynamiques |
|---|---|---|---|
| `welcome` | Post-signup | Bienvenue sur {brand} 👋 | name, login_url |
| `email_verification` | Post-signup | Vérifie ton email pour activer ton compte | name, verify_url, expires_in |
| `password_reset` | POST /auth/forgot-password | Réinitialise ton mot de passe | name, reset_url, expires_in, ip, user_agent |
| `password_changed` | Post-reset | Ton mot de passe a été modifié | name, ip, user_agent, contact_url |
| `email_changed` | Email update | Ton email a été modifié | name, old_email, new_email, contact_url |
| `login_anomaly` | Login depuis nouvelle IP/device | Connexion depuis un nouvel appareil | name, device, browser, ip, location, time, secure_url |
| `2fa_enabled` | Setup 2FA | 2FA activé sur ton compte | name, recovery_codes_attached |
| `2fa_disabled` | Disable 2FA | 2FA désactivé sur ton compte | name, ip, secure_url |
| `subscription_started` | Stripe webhook | Ton abonnement {plan} est actif | name, plan, amount, next_billing |
| `subscription_renewed` | Stripe webhook | Renouvellement réussi | name, plan, amount, next_billing, invoice_url |
| `subscription_cancelled` | Stripe webhook | Ton abonnement a été annulé | name, ends_at |
| `payment_failed` | Stripe webhook | Échec de paiement — action requise | name, amount, retry_at, billing_url |
| `invoice_paid` | Stripe invoice.paid | Facture {number} payée | name, invoice_url, pdf_url |
| `account_deletion_initiated` | DELETE /api/v1/me | Suppression du compte initiée | name, grace_period_end, undo_url |
| `account_deletion_confirmed` | Post grace period | Ton compte a été supprimé | name |
| `data_export_ready` | RGPD export | Tes données sont prêtes au téléchargement | name, download_url, expires_in |

**5 events bypass opt-out matrix** (transactionnels strict, jamais désactivables) :
`password_reset`, `email_verification`, `login_anomaly`, `payment_failed`, `invoice_paid`.

### Templates emails marketing (opt-in obligatoire)

| Template | Trigger | Opt-in via |
|---|---|---|
| `newsletter_weekly` | Cron weekly | Newsletter signup form |
| `digest_monthly` | Cron monthly | Account preferences |
| `product_announcement` | Manual / Filament | Account preferences |
| `feature_release` | Manual / Filament | Account preferences |
| `tips_and_tricks` | Cron bi-weekly | Account preferences |
| `winback` | 30j inactivity | Account preferences (pré-coché si user pas churned) |

Tous avec **unsubscribe one-click** (header `List-Unsubscribe: <mailto:...>, <https://...>`) + lien dans le footer.

### Channels supportés (driver-based)

```php
NotificationChannelRegistry — 9 channels obligatoires :
├─ in_app       (toast Sonner + bell icon dropdown)
├─ email        (Postmark / Resend / Mailgun / SES — choisir UN provider)
├─ sms          (Twilio / Vonage)
├─ whatsapp     (WhatsApp Business API via Twilio)
├─ push_web     (Web Push API — service worker)
├─ push_mob     (FCM / APNs)
├─ telegram     (Bot API)
├─ slack        (Webhook par tenant)
└─ discord      (Webhook par tenant)
```

Chaque user a une matrice `event_type × channel = boolean` (table `notification_preferences`).

### Structure d'un template email

```
resources/views/emails/{template}/
├─ subject.blade.php          (1 ligne, ≤ 70 chars, peut contenir {{ $name }})
├─ markdown.blade.php          (Markdown via @markdown — Laravel Mail Markdown)
├─ plain.blade.php             (text/plain pour client mail strict)
└─ preview.blade.php           (admin Filament preview)
```

Markdown rendu via Laravel Markdown Mailable :
```php
Mail::send(new WelcomeMail($user))
```

### Style email (responsive, dark-mode aware)

- Container max 600px
- Padding 24px desktop, 16px mobile
- Body : Inter (fallback Arial)
- H1 28px / H2 22px / H3 18px / body 16px
- Line-height 1.5 body, 1.2 headings
- Boutons : tableau HTML (compatible Outlook), 14px padding, rounded 8px
- Couleurs via tokens HSL (cf. design preamble)
- Dark mode : `@media (prefers-color-scheme: dark)` avec recompilation des contrastes (jamais juste inverser)
- Alt sur toute image
- Pas d'image background (cassé Outlook)
- Logo en haut, adresse postale + unsubscribe en footer

### Header et Footer email standardisés

**Header** :
```
[Logo brand]
{Subject équivalent / titre principal}
```

**Footer** (légal + désinscription) :
```
{Brand}, {Adresse postale} — {Pays}
SIRET {numéro} • {VAT si applicable}

Tu reçois cet email parce que {raison contextualisée}.
[Préférences] • [Se désinscrire en 1 clic]

© {year} {Brand}. {Tagline en 1 phrase}.
```

### Tracking emails

- **Open tracking** : pixel transparent `/email-pixel/{message_id}.png`
- **Click tracking** : URLs réécrites `/email-click/{message_id}/{hash}` → 302 vers cible
- **Bounce / Complaint** : webhook depuis le provider (Postmark) → marker user comme `email_bounced` ou `email_complained` (= unsubscribe automatique)
- **Suppression list** : centralisée par tenant + globale platform

### Notification in-app

- Bell icon dans header (badge count si unread)
- Dropdown shadcn Popover avec liste 5 dernières + lien "Tout voir"
- Page `/notifications` paginée avec filtres
- WebSocket via Reverb (Laravel) pour push temps réel
- Marquage lu/non-lu, archive, action inline

### Notification preferences UX

Page `/account/notifications` :

```
┌─────────────────────────────────────────────────────────────────────┐
│            in-app   email   sms   whatsapp   push_web   telegram    │
│                                                                     │
│ Compte                                                              │
│   Connexion         🔒        🔒                                     │
│   Sécurité          🔒        🔒                                     │
│   Facturation        ☑         ☑      ☐         ☐         ☐    ☐    │
│                                                                     │
│ Activité                                                            │
│   Mentions           ☑         ☑      ☐         ☐         ☑    ☐    │
│   Commentaires       ☑         ☑      ☐         ☐         ☐    ☐    │
│                                                                     │
│ Marketing                                                           │
│   Newsletter         ☐         ☑      ☐         ☐         ☐    ☐    │
│   Annonces           ☐         ☑      ☐         ☐         ☐    ☐    │
└─────────────────────────────────────────────────────────────────────┘
🔒 = transactionnel obligatoire (non-désactivable)
```

### Newsletter signup

- Component `<NewsletterForm />` réutilisable
- Double opt-in obligatoire (email de confirmation cliquable)
- Stockage `newsletter_subscribers` table avec `source` (page/article/footer)
- Welcome email automatique post-confirmation
- Unsubscribe one-click + page de confirmation + raison facultative
- Sync avec Mailchimp/Sendgrid/Brevo si externe (jamais double-source)

### Délivrabilité — règles non-négociables

- ✅ SPF + DKIM + DMARC configurés sur le domaine d'envoi
- ✅ Sub-domaine dédié envoi (`mail.{domain}`) — jamais le root
- ✅ Warmup IP progressif si volume > 1000/jour
- ✅ Pas plus de 1 email/jour/user en marketing
- ✅ Liste suppression respectée (pas de re-engagement campaigns sur unsubscribed)
- ✅ Volume bounce > 3% → pause auto + alerte admin
- ✅ Spam complaint > 0.1% → pause auto + alerte admin
- ✅ Headers : Reply-To valide, From authentifié, List-Unsubscribe RFC 8058

### Templates à shipper (.blade.php)

```
resources/views/emails/
├─ welcome/
├─ email-verification/
├─ password-reset/
├─ password-changed/
├─ login-anomaly/
├─ 2fa-enabled/
├─ subscription-started/
├─ subscription-renewed/
├─ subscription-cancelled/
├─ payment-failed/
├─ invoice-paid/
├─ account-deletion-initiated/
├─ account-deletion-confirmed/
├─ data-export-ready/
├─ newsletter-weekly/
├─ partials/
│   ├─ header.blade.php
│   ├─ footer.blade.php
│   ├─ button.blade.php
│   └─ social-links.blade.php
└─ layouts/
    └─ default.blade.php
```

### Filament admin — pages obligatoires

| Resource | Description |
|---|---|
| NotificationTemplate | CRUD des templates per event_type / channel / locale |
| NotificationDispatch | Read-only audit log, filtre status (sent/delivered/failed/bounced) |
| NotificationPreference | Vue par user, possibilité de forcer une notif |
| EmailSuppressionList | Liste des emails bloqués (bounced/complained/unsubscribed) |
| NewsletterSubscriber | Liste, export CSV, unsubscribe manuel |

### À NE JAMAIS livrer sans

- ❌ Email transactionnel sans templates Markdown
- ❌ Email marketing sans double opt-in
- ❌ Email sans unsubscribe one-click
- ❌ Notification in-app sans WebSocket (= polling = laggy)
- ❌ SPF/DKIM/DMARC manquants en prod
- ❌ Templates email non testés sur Outlook desktop (le pire client)
- ❌ Tracking pixel sans opt-in préalable (RGPD)
- ❌ Volume > 1 email marketing / jour / user
