<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\Events\ArticlePublished;
use App\Domain\Content\ValueObjects\ContentStatus;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

/**
 * Multi-tenant blog/content article.
 *
 * Tracks word_count + reading_time + quality_score so the Sprint-7 quality
 * gate (score ≥ 80 for auto-publish) can apply.
 *
 * Publishing records an `ArticlePublished` domain event consumed by the KB
 * auto-import listener (which chunks the body + computes embeddings).
 */
final class Article extends AggregateRoot
{
    /** @param list<string> $seoKeywords */
    private function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly Slug $slug,
        public readonly Locale $locale,
        public readonly string $title,
        public readonly ?string $excerpt,
        public readonly string $body,
        public readonly ?string $featuredImageUrl,
        public readonly array $seoKeywords,
        public readonly bool $isPillar,
        public ContentStatus $status,
        public int $wordCount,
        public int $readingTimeMinutes,
        public int $qualityScore,
    ) {}

    /** @param list<string> $seoKeywords */
    public static function draft(
        string $id,
        string $projectId,
        Slug $slug,
        Locale $locale,
        string $title,
        string $body,
        ?string $excerpt = null,
        ?string $featuredImageUrl = null,
        array $seoKeywords = [],
        bool $isPillar = false,
    ): self {
        $wordCount = str_word_count($body);
        $reading = max(1, (int) ceil($wordCount / 220));

        return new self(
            id: $id,
            projectId: $projectId,
            slug: $slug,
            locale: $locale,
            title: $title,
            excerpt: $excerpt,
            body: $body,
            featuredImageUrl: $featuredImageUrl,
            seoKeywords: $seoKeywords,
            isPillar: $isPillar,
            status: ContentStatus::Draft,
            wordCount: $wordCount,
            readingTimeMinutes: $reading,
            qualityScore: 0,
        );
    }

    /** @param list<string> $seoKeywords */
    public static function rehydrate(
        string $id,
        string $projectId,
        Slug $slug,
        Locale $locale,
        string $title,
        ?string $excerpt,
        string $body,
        ?string $featuredImageUrl,
        array $seoKeywords,
        bool $isPillar,
        ContentStatus $status,
        int $wordCount,
        int $readingTimeMinutes,
        int $qualityScore,
    ): self {
        return new self(
            $id, $projectId, $slug, $locale, $title, $excerpt, $body,
            $featuredImageUrl, $seoKeywords, $isPillar, $status,
            $wordCount, $readingTimeMinutes, $qualityScore,
        );
    }

    public function setQualityScore(int $score): void
    {
        $this->qualityScore = max(0, min(100, $score));
    }

    public function publish(): void
    {
        if ($this->status === ContentStatus::Published) {
            return;
        }
        $this->status = ContentStatus::Published;
        $this->recordEvent(new ArticlePublished($this->id, $this->projectId, $this->slug, $this->locale));
    }
}
