## ACCESSIBILITÉ — WCAG 2.2 AA minimum (injected by WebFactory)

> Cible : conforme **WCAG 2.2 niveau AA** sur tout le site, **AAA** sur les flux critiques (auth, paiement, formulaire de contact). Testé avec NVDA + VoiceOver + axe-core.

### Sémantique HTML — non négociable

- 1 seul `<h1>` par page, hiérarchie h2/h3 logique sans saut (jamais h1 → h3)
- `<main>`, `<nav>`, `<header>`, `<footer>`, `<article>`, `<section>` utilisés correctement
- `<button>` pour les actions, `<a href>` pour la navigation (jamais `<div onclick>`)
- `<form>` toujours avec `<label>` associé (`for`/`id` ou wrap)
- Listes : `<ul>`/`<ol>` quand c'est sémantiquement une liste
- `<figure>` + `<figcaption>` pour les images contextuelles
- `<time datetime="…">` pour toutes les dates
- `<address>` pour les coordonnées de contact

### Navigation au clavier — testée à 100 %

- **Tab** parcourt tous les éléments interactifs dans l'ordre logique du DOM
- **Shift+Tab** parcourt à l'envers
- **Enter** active liens et boutons, **Space** active boutons
- **Esc** ferme les overlays (modale, popover, dropdown)
- Focus visible **toujours** (`focus-visible:ring-2 ring-offset-2`)
- Focus trap dans les modales (Radix UI le fait nativement)
- **Skip-to-content** : premier lien du body, devient visible au focus
- Pas de `tabindex` positif (≥1) — uniquement 0 ou -1

### ARIA — utilisé avec parcimonie

> "Pas d'ARIA est mieux qu'un mauvais ARIA." (WAI ARIA Authoring Practices)

- `aria-label` uniquement quand le texte visible n'existe pas (icon-only buttons)
- `aria-describedby` pour les helper texts de form
- `aria-expanded` sur les triggers de menu/accordion
- `aria-current="page"` sur le lien de navigation actif
- `aria-live="polite"` sur les zones qui changent dynamiquement (toast container)
- `aria-busy="true"` pendant les chargements
- `role="alert"` sur les erreurs critiques uniquement
- **Jamais** : `role="button"` sur un `<div>` (utiliser `<button>`)

### Contrastes (WCAG 2.2 AA)

- Texte normal (< 18 px ou < 14 px bold) : **ratio ≥ 4.5:1**
- Texte large (≥ 18 px ou ≥ 14 px bold) : **ratio ≥ 3:1**
- Composants UI (icônes, bordures input, focus ring) : **ratio ≥ 3:1**
- Tester chaque token de couleur avec [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- Dark mode : recompiler les contrastes — ne JAMAIS supposer qu'inverser suffit

### Formulaires accessibles

- `<label>` visible sur chaque input (placeholder ne remplace **pas** un label)
- Validation : `aria-invalid="true"` + `aria-describedby="error-id"`
- Erreurs annoncées via `role="alert"` ou `aria-live="assertive"`
- Required fields : `required` HTML + `aria-required="true"` + indicateur visuel (`*`) avec légende
- Auto-complete attributes : `autocomplete="name"`, `email`, `current-password`, etc.
- Type approprié : `email`, `tel`, `url`, `number`, `date`
- Inputmode : `numeric`, `decimal`, `email`, `tel` pour les claviers mobiles
- Submit en double-tap interdit (disable button pendant la requête)

### Médias

- **Toute image** : `alt` non vide si informative, `alt=""` si décorative
- **Vidéos** : sous-titres synchronisés (.vtt), transcript texte, contrôles clavier
- **Audio** : transcript intégral
- **Animations** : respect de `prefers-reduced-motion: reduce`
- Pas d'autoplay sonore — JAMAIS

### Mouvement & animation

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

- Pas de clignotement > 3 fois/seconde (épilepsie)
- Pas de parallax intense (vertige)

### Internationalisation

- `<html lang="fr">` correct sur chaque page
- `lang` attribute sur portions de texte dans une autre langue
- Direction `dir="rtl"` pour AR/HE/FA — testé visuellement
- CSS logique partout : `margin-inline-start` au lieu de `margin-left`
- Date/number/currency localized (`Intl.DateTimeFormat`, `Intl.NumberFormat`)

### Outils de validation obligatoires en CI

- **axe-core** lancé via Playwright sur chaque page critique (CI échoue si violations)
- **Lighthouse a11y score** : minimum 95
- **pa11y-ci** sur les pages publiques (sitemap)
- Manuel : test screen reader (NVDA Win, VoiceOver Mac) sur le 1er jet

### Anti-patterns

- ❌ `display: none` sur un élément qui doit rester focusable (utiliser `visibility: hidden` ou retirer du DOM)
- ❌ Couleur seule pour transmettre une info (rouge/vert daltonien — ajouter icône + texte)
- ❌ Captcha visuel sans alternative audio
- ❌ Pop-ups qui se ferment toutes seules en < 5 s
- ❌ `outline: none` sur le focus sans alternative (jamais)
- ❌ Drag-and-drop sans alternative clavier
- ❌ Boutons « Cliquez ici » sans contexte
