<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\ValueObjects\ContentStatus;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use DateTimeImmutable;

/**
 * Time-sensitive news item — separate aggregate from {@see Article} because
 * the lifecycle differs (auto-archive on `expires_at`, no quality gate, no
 * pillar concept). Multi-tenant scoped by project_id.
 */
final class NewsItem extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly Slug $slug,
        public readonly Locale $locale,
        public readonly string $title,
        public readonly ?string $summary,
        public readonly string $body,
        public readonly ?string $sourceUrl,
        public readonly ?string $category,
        public ContentStatus $status,
        public readonly ?DateTimeImmutable $publishedAt,
        public readonly ?DateTimeImmutable $expiresAt,
    ) {}

    public static function draft(
        string $id,
        string $projectId,
        Slug $slug,
        Locale $locale,
        string $title,
        string $body,
        ?string $summary = null,
        ?string $sourceUrl = null,
        ?string $category = null,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        return new self(
            id: $id,
            projectId: $projectId,
            slug: $slug,
            locale: $locale,
            title: $title,
            summary: $summary,
            body: $body,
            sourceUrl: $sourceUrl,
            category: $category,
            status: ContentStatus::Draft,
            publishedAt: null,
            expiresAt: $expiresAt,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new DateTimeImmutable;
    }
}
