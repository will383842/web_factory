## SEO + AEO 2026 — règles imposées (injected by WebFactory)

> Cible : top 3 Google sur les requêtes commerciales + cité par ChatGPT, Perplexity, Google AI Overviews, Claude, Gemini.

### Core Web Vitals — seuils 2026

| Métrique | Seuil critique | Excellent |
|---|---|---|
| **LCP** | < 2.5 s | **< 1.5 s** |
| **INP** | < 200 ms | **< 100 ms** |
| **CLS** | < 0.1 | **< 0.05** |
| TTFB | < 600 ms | < 300 ms |
| FCP | < 1.8 s | < 1 s |

Mesuré sur **mobile 4G** avec PageSpeed Insights, pas en local sur fibre.

### Structure de page — modèle obligatoire

```html
<!DOCTYPE html>
<html lang="fr-FR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Titre unique 50-60 chars (mot-clé ciblé en début)</title>
  <meta name="description" content="Description 150-160 chars, CTA implicite">
  <link rel="canonical" href="https://example.com/page">

  <!-- OpenGraph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="…">
  <meta property="og:description" content="…">
  <meta property="og:image" content="…1200×630.jpg">
  <meta property="og:url" content="…">
  <meta property="og:locale" content="fr_FR">
  <meta property="og:site_name" content="…">

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@…">

  <!-- Hreflang multilingue -->
  <link rel="alternate" hreflang="fr-FR" href="…/fr/page">
  <link rel="alternate" hreflang="en-US" href="…/en/page">
  <link rel="alternate" hreflang="x-default" href="…/page">

  <!-- JSON-LD : 1 par page minimum -->
  <script type="application/ld+json">…</script>
</head>
```

### JSON-LD — obligatoire sur chaque page

Selon le type de page, choisir :

- **Homepage** : `Organization` + `WebSite` (avec `SearchAction`)
- **Article blog** : `Article` ou `BlogPosting` (auteur, datePublished, dateModified, image, headline, mainEntityOfPage)
- **FAQ** : `FAQPage` (mainEntity = liste de Question/Answer)
- **How-to** : `HowTo` (steps, image, tools)
- **Produit** : `Product` (offers, aggregateRating, reviews)
- **Service** : `Service` (provider, areaServed, hasOfferCatalog)
- **Event** : `Event` (startDate, location, organizer)
- **Recipe** : `Recipe`
- **Local business** : `LocalBusiness` ou subtype
- **Person** (à propos) : `Person`
- **Breadcrumb** : `BreadcrumbList` sur **toute** page non-homepage

Validation systématique via [Google Rich Results Test](https://search.google.com/test/rich-results).

### AEO — optimisation pour les LLMs

Les sites cités par ChatGPT/Perplexity/Google AI ont en commun :

1. **TL;DR au début de chaque article** : 2-3 phrases résumant la réponse, en `<p>` après le `<h1>`
2. **Format Q&R** : titre = question, paragraphe = réponse directe en 1-2 phrases, puis détails
3. **Citations explicites** : `<cite>` ou `<blockquote cite="…">` avec sources nommées
4. **Tableaux structurés** : pour comparer des options (les LLMs adorent extraire ça)
5. **Listes à puces** courtes (3-7 items) — pas de paragraphes monolithes
6. **JSON-LD `QAPage`** sur les pages FAQ avec questions individuelles
7. **Authorship** : `Person` JSON-LD avec sameAs vers LinkedIn/Twitter (E-E-A-T)
8. **Date de publication ET de mise à jour** visibles + JSON-LD
9. **Signaux d'expertise** : "10 ans d'expérience", "consultant certifié X", données chiffrées
10. **Pages détaillées** : 1500-3500 mots sur les sujets piliers (jamais des stubs courts)

### robots.txt — autoriser les crawlers IA

```
User-agent: *
Allow: /

User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: YouBot
Allow: /

User-agent: Bytespider
Allow: /

Sitemap: https://example.com/sitemap.xml
```

### Sitemap.xml — règles

- Auto-généré (jamais à la main)
- 1 fichier par type de contenu si > 1000 URLs
- `<changefreq>` réaliste (pas tout à `daily`)
- `<priority>` cohérent (homepage 1.0, pages piliers 0.9, articles 0.8, etc.)
- Hreflang inclus (`<xhtml:link rel="alternate" hreflang="…">`)
- Submit à : Google Search Console, Bing Webmaster, IndexNow API

### IndexNow — ping automatique

Sur chaque publication/mise à jour de page :
```
POST https://api.indexnow.org/indexnow
{
  "host": "example.com",
  "key": "<8-128-char-key>",
  "keyLocation": "https://example.com/{key}.txt",
  "urlList": ["https://example.com/page1", "…"]
}
```

### Performance images

- **AVIF** d'abord (taille / 4 vs JPEG), **WebP** fallback, **JPEG** ultime
- Hero image : `<img loading="eager" fetchpriority="high">`, autres `loading="lazy"`
- Toujours `width` + `height` (évite le CLS)
- `srcset` + `sizes` pour les images responsive
- Hero ≤ 200 ko, images contenu ≤ 100 ko, vignettes ≤ 30 ko
- Optimiser via `sharp` côté build, jamais en runtime

### Mots-clés — recherche obligatoire avant rédaction

- Outil : Ahrefs / Semrush / Google Keyword Planner / GSC Search Performance
- Cible : volume 100-1000/mois sur niche, 1000-10k sur sujets larges
- Difficulté ≤ 30 sur Ahrefs (pour pages neuves)
- Long-tail (4+ mots) prioritaire sur head terms
- Intent matching : informational ≠ commercial ≠ transactional ≠ navigational
- 1 mot-clé principal + 3-5 secondaires par page

### URL canoniques

- HTTPS only (jamais HTTP)
- Pas de query strings dans canonical (`?utm_source=…` retiré)
- Pas de trailing slash incohérent (choisir et s'y tenir)
- Lowercase only
- Mots séparés par `-` (jamais `_`)
- Pas plus de 4 segments (`/categorie/sous-cat/article` est OK, `/a/b/c/d/e` non)
- Slug ≤ 60 chars idéalement

### Anti-patterns SEO

- ❌ Title dupliqué entre pages
- ❌ Meta description manquante ou auto-générée
- ❌ Mots-clés stuffing dans le footer
- ❌ Liens internes en `nofollow` (gaspille le link juice interne)
- ❌ Redirections en chaîne (>2 hops)
- ❌ Pages orphelines (aucun lien interne)
- ❌ JS rendering pour le contenu critique (Google le voit, mais Bing/AI moins bien — préférer SSR/SSG)
- ❌ Pop-ups intrusifs au load (Google penalty mobile)
