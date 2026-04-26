<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Application\Content\Services\KnowledgeBaseSearchService;
use App\Application\Marketing\DTOs\InternalLinkSuggestion;
use App\Models\Article;

/**
 * Surfaces internal-link opportunities by querying the project's KB
 * (pgvector cosine search) for content semantically related to a given
 * article. Returns the top N candidates with their similarity scores.
 *
 * Spec 14 (SEO/AEO 2026) §"Internal linking" requires this for both SEO
 * weight distribution and AEO context coherence.
 */
final class InternalLinkSuggester
{
    public function __construct(private readonly KnowledgeBaseSearchService $kb) {}

    /**
     * @return list<InternalLinkSuggestion>
     */
    public function suggestForArticle(Article $article, int $limit = 5): array
    {
        $query = $article->title.' '.($article->excerpt ?? '');
        $hits = $this->kb->search((string) $article->project_id, $query, $limit + 1);

        $out = [];
        foreach ($hits as $hit) {
            // Don't link an article to itself
            if ($hit->sourceType === 'article' && $hit->sourceId === (int) $article->getKey()) {
                continue;
            }
            $out[] = new InternalLinkSuggestion(
                sourceType: $hit->sourceType,
                sourceId: $hit->sourceId ?? 0,
                anchorHint: $this->buildAnchorHint($hit->content),
                targetSlug: $hit->sourceType.'-'.($hit->sourceId ?? '?'),
                similarity: $hit->similarity,
            );

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Picks the first 3 non-stopword tokens of the chunk as a quick anchor
     * suggestion (the editor will refine in the UI).
     */
    private function buildAnchorHint(string $content): string
    {
        $stopwords = ['the', 'a', 'an', 'is', 'of', 'and', 'to', 'in', 'le', 'la', 'les', 'un', 'une', 'des', 'de', 'et'];
        $tokens = preg_split('/\s+/u', mb_strtolower(trim($content))) ?: [];
        $kept = [];
        foreach ($tokens as $t) {
            $t = preg_replace('/[^\p{L}0-9]/u', '', $t) ?? '';
            if (mb_strlen($t) >= 3 && ! in_array($t, $stopwords, true)) {
                $kept[] = $t;
                if (count($kept) === 3) {
                    break;
                }
            }
        }

        return implode(' ', $kept);
    }
}
