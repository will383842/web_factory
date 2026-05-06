## PAGES & LAYOUT — STRUCTURE OBLIGATOIRE (injected by WebFactory)

> Ces pages/composants doivent **TOUS** exister dans le projet. Le `WorkspaceVerifier` checke leur présence et bloque la livraison sinon. C'est le **DNA fonctionnel minimum** de tout site WebFactory.

### 🚨 RÈGLE D'OR : 4 LAYOUTS DISTINCTS — ne mélange JAMAIS

Tout SaaS moderne (Linear, Notion, Vercel, Claude.ai, ChatGPT, GitHub) sépare **strictement** les surfaces :

| Surface | Layout Blade | Pages concernées | Header marketing ? | Footer 4 col ? | Sidebar app ? |
|---|---|---|---|---|---|
| **Marketing** | `<x-layouts.public>` | `/`, `/about`, `/pricing`, `/blog/*`, `/contact`, `/faq`, légales, `/help`, `/changelog` | ✅ OUI | ✅ OUI | ❌ |
| **App** | `<x-layouts.app>` | `/dashboard`, `/chat/*`, fonctionnel utilisateur | ❌ Top bar minimaliste | ❌ Footer ultra-light (status + version) | ✅ OUI (collapsible) |
| **Auth** | `<x-layouts.auth>` | `/login`, `/register`, `/forgot-password`, `/reset-password`, `/2fa/*`, `/email/verify` | ❌ Logo centré seul | ❌ Liens légaux discrets | ❌ |
| **Error/Onboarding** | `<x-layouts.minimal>` | `/404`, `/500`, `/maintenance`, `/onboarding/*` | ❌ Logo centré | ❌ | ❌ |

**Mettre le header + footer marketing sur le `/dashboard` est un anti-pattern majeur.** Aucun SaaS sérieux ne fait ça. La règle "header sticky" et "footer 4 colonnes" ci-dessous s'applique **uniquement à la surface marketing**.

#### Quand utiliser quel layout ?

```php
// resources/views/pages/about.blade.php
<x-layouts.public title="À propos">
    {{ /* contenu marketing */ }}
</x-layouts.public>

// resources/views/pages/dashboard.blade.php (chat type Claude.ai)
<x-layouts.app section="chat" :case="$case">
    <x-app.chat-sidebar :cases="$userCases" />
    <main class="flex-1">
        <x-app.chat-conversation :messages="$messages" />
    </main>
</x-layouts.app>

// resources/views/auth/login.blade.php
<x-layouts.auth title="Connexion">
    <x-auth.login-form />
</x-layouts.auth>

// resources/views/errors/404.blade.php
<x-layouts.minimal>
    <x-error.404-illustration />
</x-layouts.minimal>
```

#### Layout APP — pattern conversation/chat (Claude.ai / ChatGPT-style)

Pour les apps centrées sur la conversation (juridique, médical, support, agent IA, etc.) :

```
┌─────────────────────────────────────────────────────────────────┐
│ ┌─Sidebar (260px, collapsible)───┐  ┌─Top bar (56px)─────────┐ │
│ │ Logo + brand                   │  │ {breadcrumb} {actions} │ │
│ │ ───                            │  ├────────────────────────┤ │
│ │ + Nouveau dossier              │  │                        │ │
│ │ Recherche dossiers             │  │   Zone centrale        │ │
│ │ ───                            │  │   (chat / data table)  │ │
│ │ ▾ Aujourd'hui                  │  │                        │ │
│ │   • Dossier A                  │  │                        │ │
│ │   • Dossier B                  │  │                        │ │
│ │ ▾ Cette semaine                │  │                        │ │
│ │   • Dossier C                  │  │                        │ │
│ │ ▸ Plus ancien (lazy loaded)    │  │                        │ │
│ │ ───                            │  │                        │ │
│ │ User menu (bottom)             │  │                        │ │
│ │  → Account, Billing, Logout    │  │                        │ │
│ └────────────────────────────────┘  └────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

Composants spécifiques layout app (en plus du catalogue préambule 7) :
- `<x-app.sidebar />` : nav app + collapse + recherche + user menu en bas
- `<x-app.topbar />` : breadcrumb + actions contextuelles
- `<x-app.chat-conversation />` : message bubbles, streaming, sources
- `<x-app.command-palette />` : Cmd+K (déjà dans préambule 7)
- `<x-app.empty-state />` : state initial sans dossier

Pour Avocat-IA / autre IA-app, le `/dashboard` est ce layout — **JAMAIS** le layout marketing.

### HEADER MARKETING (sticky, transparent → solide au scroll) — uniquement layout `public`

```
<header class="sticky top-0 z-40 backdrop-blur-md bg-background/80 border-b border-border/40">
  ├─ Logo (lien vers /)
  ├─ Nav principale (4-7 items max, dropdowns shadcn NavigationMenu pour les sections riches)
  ├─ SearchTrigger (icône loupe → ouvre <CommandMenu> Cmd+K)
  ├─ LocaleSwitcher (si multilingue)
  ├─ DarkModeToggle (Sonner Toast au switch)
  ├─ CTA primaire ("Sign up" / "Demo" / "Contact")
  └─ MobileMenuButton (burger → ouvre Sheet shadcn de droite)
```

- Visible sur **toutes** les pages (sauf /admin et /auth qui ont leur propre layout)
- Au scroll : `bg-background/95` + `shadow-sm` (transition 200ms)
- Mobile : nav principale dans Sheet, CTA primaire toujours visible
- Skip-to-content link en 1er enfant (focus visible only)

### FOOTER MARKETING (4 colonnes desktop, accordion mobile) — uniquement layout `public`

```
<footer class="bg-muted/40 border-t border-border/50">
  ├─ Col 1 — Brand
  │   ├─ Logo
  │   ├─ Tagline (1 phrase)
  │   ├─ NewsletterForm (email + button "S'abonner")
  │   ├─ Social links (LinkedIn, Twitter/X, GitHub, YouTube)
  │   └─ LocaleSwitcher si multilingue
  ├─ Col 2 — Produit
  │   ├─ Features
  │   ├─ Pricing
  │   ├─ Changelog
  │   ├─ Roadmap
  │   └─ Status page (https://status.{domain})
  ├─ Col 3 — Société
  │   ├─ À propos
  │   ├─ Blog
  │   ├─ Carrières (si applicable)
  │   ├─ Contact
  │   └─ Presse / Médias
  └─ Col 4 — Légal
      ├─ Mentions légales
      ├─ CGU / Terms
      ├─ CGV (si commercial)
      ├─ Politique de confidentialité
      ├─ Politique cookies
      └─ Accessibilité (statement)

  └─ Bottom strip
      ├─ Copyright "© {year} {brand}. Tous droits réservés."
      ├─ "Made with ❤️ by {author}"
      ├─ Cookie settings link (réouvre <CookieBanner> en mode édition)
      └─ Sitemap link (HTML, pas XML)
</footer>
```

- Mobile : chaque colonne devient un `<details>` accordion
- **Présent uniquement sur layout `public`** — pas sur `/dashboard`, `/chat/*`, auth, errors

### FOOTER APP (ultra-light, layout `app`)

Sur les pages app, optionnel et minimaliste :

```html
<footer class="px-4 py-2 text-xs text-muted-foreground flex justify-between items-center border-t">
  <span>v{version} • <a href="/status">Status</a></span>
  <span><a href="/help">Aide</a> • <a href="/feedback">Feedback</a></span>
</footer>
```

### LAYOUT AUTH / ERREUR — minimaliste

```html
<body class="min-h-screen bg-muted/30 grid place-items-center p-4">
  <div class="w-full max-w-md">
    <a href="/" class="block mx-auto mb-8 w-32"><x-logo /></a>
    <div class="rounded-2xl border bg-card p-8 shadow-sm">
      {{ $slot }}
    </div>
    <div class="mt-6 text-center text-xs text-muted-foreground">
      <a href="/mentions-legales">Mentions</a> • <a href="/privacy">Privacy</a>
    </div>
  </div>
</body>
```

### PAGES PUBLIQUES OBLIGATOIRES — toutes doivent exister

| Route | Type | Contenu minimum |
|---|---|---|
| `/` | Marketing | Hero + Features (3-6) + Social proof + Pricing teaser + FAQ + CTA final |
| `/a-propos` (`/about`) | Marketing | Mission + Histoire + Équipe + Valeurs |
| `/pricing` | Marketing | Plans avec toggle mensuel/annuel + comparatif features + FAQ pricing |
| `/contact` | Marketing | Form (nom, email, sujet, message, RGPD) + email + téléphone + adresse + map (si physique) |
| `/faq` | Support | Catégories + accordéon questions (≥ 15 Q&R) + JSON-LD `FAQPage` |
| `/blog` | Content | Listing paginé (12/page) + filtres catégorie/tag + recherche |
| `/blog/{slug}` | Content | Article avec TL;DR + TOC sticky + auteur + reading time + related + share |
| `/centre-aide` (`/help`) | Support | Catégories d'aide + recherche + lien contact |
| `/cas-clients` (`/case-studies`) | Marketing | Grid cards + détail par client + métriques |
| `/changelog` | Marketing | Liste timeline des releases + RSS feed |
| `/mentions-legales` | Légal | Éditeur + hébergeur + propriété intellectuelle + données |
| `/cgu` | Légal | Texte CGU complet (utiliser un template juriste) |
| `/cgv` | Légal | Si commercial — sinon `/cgu` couvre |
| `/politique-confidentialite` | Légal | RGPD : finalités, durées, droits, DPO contact |
| `/politique-cookies` | Légal | Liste exhaustive cookies + catégorie + finalité + durée |
| `/accessibilite` | Légal | Déclaration WCAG 2.2 AA + procédure de signalement |
| `/recherche` (`/search`) | Functional | Page de résultats globale (Cmd+K en page complète) |
| `/sitemap.xml` | Tech | Auto-généré, soumis à GSC + Bing |
| `/sitemap` | UX | Sitemap HTML lisible humain |
| `/robots.txt` | Tech | Autorise GPTBot/ClaudeBot/PerplexityBot (cf. préambule SEO) |
| `/manifest.webmanifest` | PWA | name, icons, theme_color, display |
| `/sw.js` | PWA | Service worker (network-first navigate, cache-first static) |
| `/rss.xml` | Content | Feed RSS du blog (atom 1.0) |
| `/404` | Error | Illustration + "Oups, page introuvable" + Search + lien home |
| `/500` | Error | Illustration + "On a un souci, on est dessus" + status link |
| `/maintenance` | Error | Illustration + ETA + email contact |

### PAGES B2C / SaaS OBLIGATOIRES (si app)

| Route | Type | Contenu |
|---|---|---|
| `/auth/login` | Auth | Form email+password + magic link + SSO + "Forgot password" |
| `/auth/register` | Auth | Form + GDPR checkbox + lien CGU + double opt-in |
| `/auth/email/verify` | Auth | Page de redirection après click sur email de vérification |
| `/auth/forgot-password` | Auth | Form email seul + message non-leaking |
| `/auth/reset-password` | Auth | Form nouveau password + confirmation |
| `/auth/2fa/setup` | Auth | QR code + secret + recovery codes téléchargeables |
| `/auth/2fa/verify` | Auth | Form 6 digits + lien recovery |
| `/onboarding/welcome` | UX | Étape 1 du tutoriel + skip + progress |
| `/onboarding/profile` | UX | Étape 2 — completer profil |
| `/onboarding/preferences` | UX | Étape 3 — notifications, locale, etc. |
| `/dashboard` | App | Home utilisateur connecté |
| `/account/profile` | Settings | Nom, email, avatar, bio, locale, timezone |
| `/account/security` | Settings | Password change, 2FA, sessions actives, devices |
| `/account/notifications` | Settings | Préférences par channel (email, push, in-app) |
| `/account/billing` | Settings | Plan actuel, factures, méthode paiement, upgrade/downgrade |
| `/account/data` | RGPD | "Exporter mes données" (art. 15) + "Supprimer mon compte" (art. 17) |

### LAYOUT PATTERNS

- **Container** : `container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8`
- **Section padding** : `py-16 sm:py-20 lg:py-32`
- **Above-the-fold** : H1 + sous-titre + 1 CTA visible **sans scroll** (testé 360×640)
- **Alternance** : sections successives `bg-background` ↔ `bg-muted/40`
- **Aside / Sidebar** : `lg:sticky lg:top-24` pour TOC ou filtres
- **Grids** : `grid gap-6 sm:gap-8 lg:gap-10`, breakpoints au minimum

### À NE JAMAIS LIVRER SANS

- ❌ Page sans header ou footer (hors pages auth qui ont leur propre layout simplifié)
- ❌ Lien dans le footer qui pointe vers `#` ou page 404
- ❌ Page légale "lorem ipsum" — toujours du vrai contenu (FR + EN minimum)
- ❌ Cookie banner manquant en EU
- ❌ 404 / 500 par défaut Laravel/Next.js (toujours custom branded)
- ❌ Sitemap.xml ou robots.txt manquants
