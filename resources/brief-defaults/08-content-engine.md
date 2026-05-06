## CONTENT ENGINE & BLOG (injected by WebFactory)

> Tout site WebFactory doit avoir un **moteur de contenu opérationnel dès le jour 1**, avec articles seed, calendrier de publication, et structure éditoriale qui maximise SEO + AEO (citations par ChatGPT/Perplexity/Google AI).

### Articles seed obligatoires (au lancement)

**Minimum 6 articles** rédigés et publiés avant le go-live, répartis en 3 catégories :

| # | Type | Mot-clé cible | Longueur |
|---|---|---|---|
| 1 | Pillar (sujet principal) | head term niche | 2500-4000 mots |
| 2 | Guide pratique | "comment {action}" | 1800-2500 mots |
| 3 | Comparatif | "{us} vs {concurrent}" | 1500-2200 mots |
| 4 | FAQ longue | "qu'est-ce que {sujet}" | 1200-1800 mots |
| 5 | Use case / Cas client | "{persona} {résultat}" | 1500-2000 mots |
| 6 | Annonce produit | release notes / launch | 800-1200 mots |

Articles seed = autorité immédiate aux yeux de Google + matière première pour les LLMs.

### Structure d'un article (NON-NÉGOCIABLE)

```markdown
# {Titre — H1, mot-clé en début si possible}

> **TL;DR** — {2-3 phrases résumant la réponse complète, AEO-friendly}

---

## Sommaire {TOC auto-générée des H2}

## {H2 #1 — Question / Sous-thème}
{Réponse directe en 1-2 phrases en gras}, puis développement.

### {H3 si nécessaire}

> **Citation experte** : {auteur — source — date}

| Comparatif tableau | Col 1 | Col 2 |
|---|---|---|
| ... | ... | ... |

- Liste à puces (3-7 items max)
- ...

```code
{Snippet pertinent avec syntax highlighting}
```

## {H2 #2}
...

## FAQ
**{Question 1 ?}**
{Réponse 2-3 phrases}.

**{Question 2 ?}**
...

## Pour aller plus loin
- [{Article lié 1}](/blog/...)
- [{Article lié 2}](/blog/...)
- [{Ressource externe}](https://...)

---

{AuthorCard avec bio}
```

### Métadonnées article (champs DB obligatoires)

```php
$article = [
    'slug' => 'kebab-case',
    'title' => '... (50-65 chars)',
    'subtitle' => '... (max 100 chars, optional)',
    'excerpt' => '... (160 chars max, sert de meta description)',
    'tldr' => '... (2-3 phrases)',
    'content' => 'Markdown',
    'reading_time_minutes' => 8, // calculé : 200 mots/min
    'published_at' => '...',
    'updated_at' => '...',  // affiché publiquement si > 30j depuis published
    'author_id' => ...,
    'category_id' => ...,
    'tags' => ['...', '...'], // 3-5 max
    'cover_image' => '...',  // AVIF, 1200×630 (OG-ratio)
    'cover_image_alt' => '...',
    'meta_title' => '...',  // surcharge title si fourni
    'meta_description' => '...',  // surcharge excerpt si fourni
    'json_ld' => [...],  // Article + Author + BreadcrumbList
    'related_article_ids' => [...],  // 3-5
    'word_count' => ...,
    'aeo_score' => ...,  // calculé (TL;DR ✅, FAQ ✅, tableau ✅, etc.)
    'language' => 'fr-FR',
    'translations' => [...],  // si multilingue
];
```

### Tables DB obligatoires

```
articles
  - id, slug (unique), title, content, ... (cf. ci-dessus)

categories
  - id, slug, name, description, parent_id (nullable)

tags
  - id, slug, name

article_tag (pivot)

authors
  - id, slug, name, avatar_url, bio, social_links (JSON)

article_views (audit + analytics)
  - article_id, viewed_at, ip_hash, user_agent_hash, referrer
```

### Fonctionnalités blog (toutes obligatoires)

- ✅ Listing paginé (12/page) avec filtres catégorie + tag + recherche full-text
- ✅ Article detail avec TOC sticky + reading progress + share buttons + related (3-5) + author bio
- ✅ Auteur page : `/blog/auteurs/{slug}` avec ses articles
- ✅ Catégorie page : `/blog/categories/{slug}`
- ✅ Tag page : `/blog/tags/{slug}`
- ✅ Recherche full-text Meilisearch ou pgvector
- ✅ RSS feed `/rss.xml` (Atom 1.0)
- ✅ Sitemap blog séparé `/sitemap-blog.xml`
- ✅ Newsletter signup en bas de chaque article (avec context : "Si vous avez aimé cet article, recevez le suivant")
- ✅ Lecture similaire / "À lire aussi" automatique (par tags + catégorie)
- ✅ Si article > 1500 mots : table des matières sticky droite + reading progress bar top
- ✅ Mode lecture (CSS reading) : police plus grande, line-height augmenté, max-w-prose
- ✅ Print stylesheet (`@media print`) : pas de header/footer, image en N&B

### AEO — Optimisation Generative Search (cf. préambule SEO)

Chaque article doit avoir :
1. **TL;DR** au début (3 phrases max) — EXTRA important
2. **Format Q&R** : 1 H2 par question
3. **Citations explicites** : `<cite>` ou `<blockquote cite="...">` avec source
4. **Tableau comparatif** quand pertinent
5. **JSON-LD** `Article` + `BreadcrumbList` + `FAQPage` si section FAQ
6. **Authorship** : `Person` JSON-LD avec sameAs vers LinkedIn/Twitter (E-E-A-T)
7. **Date publication ET mise à jour** visibles + JSON-LD
8. **Word count** ≥ 1500 sur les piliers, ≥ 800 minimum

### Calendrier de publication

- **Minimum 1 article / semaine** (52/an)
- Publication automatisée via cron Laravel (article avec `published_at` futur reste en draft jusqu'à la date)
- Notification Telegram/Slack à chaque publication
- Tweet/post LinkedIn auto à la publication (via webhook)

### Système de drafts & versions

```
articles
  - status: draft | scheduled | published | archived
  - revision_id  → article_revisions
article_revisions
  - article_id, content, author_id, created_at  (1 par save)
```

Drafts visibles uniquement aux auteurs + admins. Bouton "Preview" pour voir le rendu sans publier.

### Comments (optionnel mais recommandé)

Si commentaires activés :
- Disqus (simple) OU intégrés (table `comments` + modération + spam filter)
- Auth requise (pas anonyme — réduit le spam)
- Notification email à l'auteur sur nouveau commentaire
- Moderation queue dans admin Filament

### Stats par article

Dashboard Filament Author par article :
- Vues (total + 7j + 30j)
- Reading completion rate
- Bounce rate
- Sources de trafic (referrer)
- Conversion newsletter (visit → signup ratio)

### Tags / catégories — règles éditoriales

- **Max 5 tags par article** (sinon dilution SEO)
- **Catégorie unique** (parent ou enfant) par article
- Hiérarchie max 2 niveaux
- Slugs ASCII lowercase (cf. règle générale)

### À ne jamais livrer sans

- ❌ Blog sans articles seed
- ❌ Article < 800 mots sur les sujets piliers
- ❌ Article sans TL;DR
- ❌ Article sans `Article` JSON-LD
- ❌ Sitemap blog manquant
- ❌ RSS feed manquant
- ❌ Auteur sans bio (anti E-E-A-T)
- ❌ Date de publication manquante ou cachée
