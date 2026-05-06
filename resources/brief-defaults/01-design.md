## DESIGN SYSTEM — RÈGLES ABSOLUES (injected by WebFactory, 2026 spec)

> Ce préambule est injecté automatiquement en tête de chaque brief par WebFactory.
> Il prime sur les éventuelles contradictions plus loin dans le document.
> Édition centralisée : `/admin/manage-brief-defaults` dans WebFactory.

### Stack visuelle imposée

- **Tailwind CSS v4** (config minimale, tout via `@theme` et tokens CSS variables)
- **shadcn/ui** dernière version stable, composants copiés dans `components/ui/`
  (jamais de `npm install` direct — toujours via `npx shadcn add <component>`)
- **Radix UI primitives** (sous shadcn) pour toute l'a11y (Dialog, Popover, DropdownMenu, Tooltip…)
- **Framer Motion** pour les animations (spring, layout, presence)
- **Heroicons v2** pour les icônes (cohérence avec Filament admin)
- **Lucide** uniquement si Heroicons ne couvre pas le besoin
- **Sonner** (shadcn/ui Toast) pour les toasts — jamais d'`alert()`

### Typographie

- **Inter** (variable font) via `next/font` ou `bunny.net` (preconnect + `font-display: swap`)
- **Geist Mono** pour le code et les chiffres tabulaires
- Échelle modulaire : `text-xs/sm/base/lg/xl/2xl/3xl/4xl/5xl/6xl` — jamais d'unités custom
- Line-height : `leading-tight` titres, `leading-relaxed` corps, `leading-none` boutons
- Letter-spacing : `tracking-tight` titres ≥ `text-3xl`, `tracking-normal` ailleurs

### Tokens couleur (HSL avec opacity modifier Tailwind v4)

```css
@theme {
  --color-background: 0 0% 100%;
  --color-foreground: 240 10% 3.9%;
  --color-primary: 240 5.9% 10%;
  --color-primary-foreground: 0 0% 98%;
  --color-muted: 240 4.8% 95.9%;
  --color-muted-foreground: 240 3.8% 46.1%;
  --color-accent: 240 4.8% 95.9%;
  --color-destructive: 0 84.2% 60.2%;
  --color-border: 240 5.9% 90%;
  --color-ring: 240 10% 3.9%;
  --radius: 0.75rem;
}
.dark {
  --color-background: 240 10% 3.9%;
  --color-foreground: 0 0% 98%;
  /* … inverse pour dark mode */
}
```

Usage : `bg-background`, `text-foreground/80`, `border-border`, `ring-2 ring-ring/50`.

### Mode sombre — OBLIGATOIRE

- Toggle 3 états : `system` / `light` / `dark`
- Utiliser `next-themes` (Next.js) ou équivalent
- Stockage localStorage + respect `prefers-color-scheme` au premier load
- Aucune classe `bg-white`, `text-black`, `bg-gray-900` en dur — toujours via tokens

### Responsive — MOBILE-FIRST

- Breakpoints Tailwind : `sm` (640) / `md` (768) / `lg` (1024) / `xl` (1280) / `2xl` (1536)
- Touch targets : minimum **44 × 44 px** (Apple HIG / WCAG 2.5.5)
- **Container queries** (Tailwind v4) pour les composants réutilisables : `@container` + `@md:flex-row`
- Pas de scroll horizontal sur mobile — testé via Chrome DevTools 360 × 640

### Animations — règles strictes

- Toute animation respecte `@media (prefers-reduced-motion: reduce)` → désactivée
- Durées : 150 ms (micro-interactions), 250 ms (transitions composants), 400 ms max (hero)
- Easing : `ease-out` (entrées), `ease-in` (sorties), `cubic-bezier(0.4, 0, 0.2, 1)` (default)
- Framer Motion `<AnimatePresence>` pour les unmounts
- **View Transitions API** (`document.startViewTransition`) pour les changements de route quand supporté
- Pas de `will-change` permanent — uniquement pendant l'animation

### Patterns visuels 2026

- **Cards** : `rounded-2xl border border-border/50 bg-card shadow-sm hover:shadow-md transition-shadow`
- **Boutons primaires** : `rounded-xl px-5 py-2.5 font-medium tracking-tight` + variantes shadcn
- **Glass** (modéré, hero seulement) : `backdrop-blur-md bg-background/70 border border-border/40`
- **Mesh gradients** (hero) : sur SVG dégradés radiaux superposés, opacity 30-50 %
- **Skeletons** : composant shadcn `<Skeleton />` partout — jamais de spinner centré
- **Focus** : `focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2`
- **Empty states** : illustration + heading + body + CTA primaire (jamais de page vide)
- **Loading buttons** : icône spinner + label "Loading…" + disabled (`<Button disabled>`)
- **Form errors** : tooltip rouge + icône + message clair sous le champ + `aria-invalid="true"`
- **Microcopy** : tutoiement informel "Tu" en français, "you" en anglais

### Hiérarchie de page (modèle)

```
<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
  <header class="mb-12 lg:mb-16">
    <h1 class="text-4xl lg:text-6xl font-bold tracking-tight">…</h1>
    <p class="mt-4 max-w-2xl text-lg text-muted-foreground">…</p>
  </header>
  <section class="grid gap-8 lg:gap-12">…</section>
</main>
```

### Performance visuelle (Core Web Vitals 2026)

- **LCP** < 1.5 s (image hero compressée AVIF/WebP, `<img loading="eager" fetchpriority="high">`)
- **INP** < 200 ms (debounce inputs, defer non-critical JS)
- **CLS** < 0.1 (réserver l'espace : `<img>` toujours avec `width`/`height` ou aspect-ratio)
- Hero image : AVIF en premier, WebP fallback, JPEG fallback, max 200 ko
- Polices : `font-display: swap`, fallback `system-ui`, sub-set via `next/font`
- Lazy-load tout sauf le above-the-fold

### À ne JAMAIS faire

- ❌ Spinner centré sur toute la page (utiliser skeletons)
- ❌ Modale qui prend tout l'écran sans bouton de fermeture évident
- ❌ Toast qui dure < 3 s ou > 7 s (sweet spot 4 s)
- ❌ Couleurs en dur (`bg-blue-500`) — toujours via tokens
- ❌ `position: absolute` pour les layouts (utiliser grid/flex)
- ❌ Texte centré sur paragraphe long (lisibilité)
- ❌ Underline sur les liens à l'intérieur d'un bouton
- ❌ Carrousels auto-play sans pause (a11y)
- ❌ Captcha v2 obstructifs (utiliser Cloudflare Turnstile invisible)
