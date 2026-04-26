<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Application\Marketing\DTOs\JsonLdSchema;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Generates schema.org JSON-LD payloads for the 5 core SEO/AEO schemas:
 *
 *  - WebSite           — site identity + SearchAction
 *  - Organization      — brand + sameAs
 *  - Article           — content articles
 *  - FAQPage           — combined Q&A list (AEO essential)
 *  - BreadcrumbList    — navigation breadcrumb
 *
 * The output is structurally validated by tests; no external HTTP calls.
 */
final class JsonLdGenerator
{
    public function website(Project $project, string $baseUrl): JsonLdSchema
    {
        return new JsonLdSchema('WebSite', [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $project->name,
            'url' => $baseUrl,
            'inLanguage' => $project->locale,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => rtrim($baseUrl, '/').'/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ]);
    }

    public function organization(Project $project, string $baseUrl): JsonLdSchema
    {
        return new JsonLdSchema('Organization', [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $project->name,
            'url' => $baseUrl,
            'logo' => rtrim($baseUrl, '/').'/logo.png',
        ]);
    }

    public function article(Article $article, string $baseUrl): JsonLdSchema
    {
        return new JsonLdSchema('Article', [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'inLanguage' => $article->locale,
            'image' => $article->featured_image_url,
            'datePublished' => $article->published_at?->toAtomString(),
            'wordCount' => $article->word_count,
            'keywords' => array_values((array) $article->seo_keywords),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => rtrim($baseUrl, '/').'/'.$article->slug,
            ],
        ]);
    }

    /**
     * AEO-critical: a single FAQPage entity with all Q&As — that's what
     * Google / Bing / Perplexity scrape for direct answers.
     *
     * @param Collection<int, Faq>|iterable<Faq> $faqs
     */
    public function faqPage(iterable $faqs): JsonLdSchema
    {
        $items = [];
        foreach ($faqs as $faq) {
            $items[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ];
        }

        return new JsonLdSchema('FAQPage', [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items,
        ]);
    }

    /**
     * @param list<array{name: string, url: string}> $breadcrumbs
     */
    public function breadcrumb(array $breadcrumbs): JsonLdSchema
    {
        $items = [];
        foreach ($breadcrumbs as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return new JsonLdSchema('BreadcrumbList', [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }
}
