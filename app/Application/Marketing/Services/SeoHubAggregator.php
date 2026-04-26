<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Models\Article;
use App\Models\Faq;
use App\Models\News;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates SEO/AEO health metrics across all content types of all
 * projects (or a single one). Used by the Filament SEO Hub page.
 */
final class SeoHubAggregator
{
    public function __construct(private readonly AeoOptimizer $aeo) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(?int $projectId = null): array
    {
        $pages = Page::query()->when($projectId, fn ($q) => $q->where('project_id', $projectId));
        $articles = Article::query()->when($projectId, fn ($q) => $q->where('project_id', $projectId));
        $faqs = Faq::query()->when($projectId, fn ($q) => $q->where('project_id', $projectId));
        $news = News::query()->when($projectId, fn ($q) => $q->where('project_id', $projectId));

        return [
            'pages' => [
                'total' => $pages->count(),
                'published' => (clone $pages)->where('status', 'published')->count(),
                'by_locale' => (clone $pages)
                    ->selectRaw('locale, count(*) as c')
                    ->groupBy('locale')
                    ->get()
                    ->pluck('c', 'locale')
                    ->all(),
            ],
            'articles' => [
                'total' => $articles->count(),
                'published' => (clone $articles)->where('status', 'published')->count(),
                'pillar' => (clone $articles)->where('is_pillar', true)->count(),
                'avg_quality' => (int) round((float) (clone $articles)->avg('quality_score')),
                'avg_word_count' => (int) round((float) (clone $articles)->avg('word_count')),
            ],
            'faqs' => [
                'total' => $faqs->count(),
                'published' => (clone $faqs)->where('status', 'published')->count(),
                'featured' => (clone $faqs)->where('is_featured', true)->count(),
            ],
            'news' => [
                'total' => $news->count(),
                'active' => (clone $news)
                    ->where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })->count(),
            ],
            'kb_chunks' => DB::table('knowledge_chunks')
                ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
                ->count(),
        ];
    }

    /**
     * Computes the average AEO score on the published articles.
     * Slow on big corpora — call asynchronously from a Filament page.
     */
    public function averageAeoScore(?int $projectId = null, int $sampleLimit = 50): int
    {
        $rows = Article::query()
            ->where('status', 'published')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest('id')
            ->limit($sampleLimit)
            ->get(['body']);

        if ($rows->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += $this->aeo->evaluate((string) $row->body)['score'];
        }

        return (int) round($total / $rows->count());
    }
}
