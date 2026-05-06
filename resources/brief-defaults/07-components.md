## COMPOSANTS UI IMPOSÉS (injected by WebFactory)

> Catalogue de **30+ composants** que tout projet WebFactory doit avoir. Tous accessibles, dark-mode aware, responsive, animés respectueusement de `prefers-reduced-motion`. Backbone : shadcn/ui (Radix UI dessous).

### Marketing

#### `<Hero />`
```tsx
<Hero
  title="..."          // string, max 8 mots
  subtitle="..."       // string, max 25 mots
  primaryCta={{ label: "...", href: "..." }}
  secondaryCta={{ label: "...", href: "..." }}  // optional
  image="..."          // AVIF + WebP fallback, 1920×1080 max
  badge="..."          // optional pill au-dessus du titre
/>
```
Layout : split 60/40 desktop, stack mobile. Image lazy mais `fetchpriority="high"`.

#### `<Features features={[...]} layout="grid|list|alternating" />`
Liste de 3-9 features. Chaque feature = `{ icon: ReactNode, title: string, description: string, href?: string }`.

#### `<Testimonials items={[...]} variant="grid|carousel|wall" />`
Témoignages clients. Photo + nom + poste + entreprise + texte + rating optionnel.

#### `<PricingTable plans={[...]} interval="month|year" />`
Toggle mensuel/annuel + 3-4 plans + features check/cross + CTA par plan + "Plus populaire" badge.

#### `<FAQAccordion items={[...]} category?="..." />`
Radix Accordion. JSON-LD `FAQPage` injecté automatiquement.

#### `<CallToActionStrip />`
Bandeau plein écran avec H2 + paragraphe + 1-2 CTA. À placer en bas des pages marketing.

#### `<LogoCloud logos={[...]} title="Ils nous font confiance" />`
Grille de logos clients en niveaux de gris, hover = couleur.

#### `<StatsGrid stats={[...]} />`
4-6 chiffres-clés avec animation count-up à la première vue (Framer Motion `whileInView`).

### Navigation

#### `<CommandMenu />` (Cmd+K, obligatoire)
Modal Radix avec recherche fuzzy (cmdk). Catégories : Pages, Articles, Actions, Help.

#### `<BreadcrumbNav items={[...]} />`
Avec JSON-LD `BreadcrumbList`. Chevron icon shadcn.

#### `<Pagination current={n} total={m} onChange={fn} />`
shadcn Pagination. 10 items par page max sur mobile.

#### `<TableOfContents items={[...]} sticky />`
Sticky droite desktop, top mobile. Highlight section active au scroll (IntersectionObserver).

### Form

#### `<NewsletterForm />`
Email + button "S'abonner". Double opt-in. Confirmation toast Sonner.

#### `<ContactForm />`
Nom + email + sujet + message + GDPR checkbox + reCAPTCHA invisible (Cloudflare Turnstile recommandé).

#### `<SearchInput />`
Combobox shadcn, debounce 250ms, résultats inline ou redirection /search.

#### `<DateRangePicker />` / `<DatePicker />`
shadcn + react-day-picker.

#### `<MultiSelect options={[...]} value={[...]} onChange={fn} />`
shadcn Combobox extended.

#### `<FileDrop accept="..." onFiles={fn} maxSize={n} />`
Dropzone, preview, progress. Drag-and-drop.

### Feedback

#### `<EmptyState icon title description ctaLabel ctaHref />`
**Obligatoire sur toute page qui peut être vide.** Illustration + heading + paragraph + CTA. Pas de page vide.

#### `<LoadingSkeleton />`
Variantes : list, card, table, article. **Jamais** de spinner centré pleine page.

#### `<ErrorBoundary fallback={...} />`
Class component React. Sur chaque feature critique.

#### `<Toast />` (via Sonner)
4 variants : success / info / warning / error. Auto-dismiss 4s. Action button optionnelle.

#### `<ProgressBar value={n} indeterminate? />`
Pour uploads, jobs longs.

### Modals & Overlays

#### `<Dialog />` / `<Sheet />` / `<Drawer />`
Radix Dialog (centered), Sheet (side), Drawer (bottom mobile, full sheet desktop).

#### `<Popover />` / `<Tooltip />` / `<HoverCard />`
Radix. Délai 500ms pour les Tooltip.

#### `<DropdownMenu />`
Radix. Avec sous-menus, raccourcis claviers, séparateurs.

#### `<ConfirmDialog message confirmLabel onConfirm />`
Wrapper sur Dialog pour les actions destructives.

### Content / Blog

#### `<BlogCard article={...} />`
Image + titre + extrait + auteur + date + reading time + tags. Hover lift subtil.

#### `<ArticleHeader article={...} />`
Titre + sous-titre + auteur (avatar + nom + bio link) + date + reading time + share + cover image.

#### `<RelatedArticles articles={[...]} max={3} />`
Cards en bas de chaque article.

#### `<ShareButtons url title />`
Copy link + Twitter/X + LinkedIn + Email. Tracking event sur click.

#### `<AuthorCard author={...} />`
Avatar + nom + bio + social links. En bas de chaque article.

#### `<ReadingProgress />`
Barre fixe top, indique % lu de l'article.

### GDPR / Légal

#### `<CookieBanner />` **OBLIGATOIRE EN UE**
Modal bottom au 1er visit. Boutons "Tout accepter" / "Tout refuser" / "Personnaliser".
Catégories : Nécessaires (toggle disabled, on) / Analytics / Marketing / Préférences.
Stockage : cookie `cookie_consent` (1 an) + dispatch event pour activer scripts conditionnels.

#### `<GDPRDataExportButton />` (compte utilisateur)
Click → API call `/api/v1/me/export` → download JSON.

#### `<GDPRAccountDeleteButton />` (compte utilisateur)
Confirm dialog double + delete via `/api/v1/me`.

### Utility

#### `<DarkModeToggle />`
3 états (system / light / dark). next-themes ou équivalent.

#### `<LocaleSwitcher locales={[...]} current={...} />`
Dropdown avec flag + nom langue. Préserve le path en switchant.

#### `<CopyButton text />`
Click → copy + toast success.

#### `<ImageWithFallback src alt fallback aspectRatio />`
Wrapper `<img>` ou `<Image>` Next.js avec fallback skeleton + error state.

### Conventions communes

- **Naming** : PascalCase, suffixe `Props` interface, default export
- **Variants** : via `cva` (class-variance-authority) — **jamais** de className concat ad-hoc
- **A11y** : Radix UI partout où possible (gère le focus trap, aria, etc.)
- **Storybook** : 1 story par composant + 1 story par variant
- **Tests** : Vitest pour le rendu + Playwright pour les flows interactifs
- **Documentation** : un README par dossier `components/{category}/README.md`

### À NE JAMAIS livrer sans

- ❌ Spinner pleine page (utiliser skeletons)
- ❌ Toast qui dure < 3s ou > 7s (sweet spot 4s)
- ❌ Modal sans `Esc` ou click extérieur pour fermer
- ❌ Form sans `aria-invalid` sur erreur
- ❌ Composant sans state d'erreur
- ❌ Composant sans empty state si applicable
- ❌ Hover effect sans alternative tactile (mobile)
