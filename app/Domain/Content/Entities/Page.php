<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\Events\PagePublished;
use App\Domain\Content\ValueObjects\ContentStatus;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

/**
 * Multi-tenant content page (one row per (project_id, slug, locale)).
 *
 * Lifecycle: Draft -> (Scheduled) -> Published -> Archived. The publish()
 * factory promotes from any non-published state and records a PagePublished
 * domain event consumed by the KB auto-import listener.
 */
final class Page extends AggregateRoot
{
    /**
     * @param list<array<string, mixed>> $contentBlocks
     * @param array<string, mixed> $metaTags
     */
    private function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly Slug $slug,
        public readonly Locale $locale,
        public readonly string $title,
        public readonly string $type,
        public ContentStatus $status,
        public array $contentBlocks,
        public array $metaTags,
    ) {}

    /**
     * @param list<array<string, mixed>> $contentBlocks
     * @param array<string, mixed> $metaTags
     */
    public static function draft(
        string $id,
        string $projectId,
        Slug $slug,
        Locale $locale,
        string $title,
        string $type = 'static',
        array $contentBlocks = [],
        array $metaTags = [],
    ): self {
        return new self(
            id: $id,
            projectId: $projectId,
            slug: $slug,
            locale: $locale,
            title: $title,
            type: $type,
            status: ContentStatus::Draft,
            contentBlocks: $contentBlocks,
            metaTags: $metaTags,
        );
    }

    /**
     * @param list<array<string, mixed>> $contentBlocks
     * @param array<string, mixed> $metaTags
     */
    public static function rehydrate(
        string $id,
        string $projectId,
        Slug $slug,
        Locale $locale,
        string $title,
        string $type,
        ContentStatus $status,
        array $contentBlocks,
        array $metaTags,
    ): self {
        return new self($id, $projectId, $slug, $locale, $title, $type, $status, $contentBlocks, $metaTags);
    }

    public function publish(): void
    {
        if ($this->status === ContentStatus::Published) {
            return;
        }
        $this->status = ContentStatus::Published;
        $this->recordEvent(new PagePublished($this->id, $this->slug, $this->locale));
    }

    public function archive(): void
    {
        $this->status = ContentStatus::Archived;
    }
}
