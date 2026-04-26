<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Listeners;

use App\Domain\Content\Events\ArticlePublished;
use App\Domain\Content\Events\PagePublished;
use App\Infrastructure\Content\PgVectorKnowledgeBase;
use App\Models\Article;
use App\Models\Page;

/**
 * Listens to PagePublished + ArticlePublished and ingests the body into
 * the multi-tenant knowledge base. The chunking is naive in Sprint 7
 * (one chunk per content piece); Sprint 19 will introduce token-aware
 * sliding-window chunking.
 */
final class IngestPublishedContentToKnowledgeBase
{
    public function __construct(private readonly PgVectorKnowledgeBase $kb) {}

    public function handlePagePublished(PagePublished $event): void
    {
        $row = Page::query()->find($event->aggregateId());
        if ($row === null) {
            return;
        }

        $blocks = (array) ($row->content_blocks ?? []);
        $body = collect($blocks)
            ->map(static fn ($b): string => is_array($b) ? (string) ($b['text'] ?? '') : (string) $b)
            ->implode("\n\n");

        if (trim($body) === '') {
            $body = $row->title;
        }

        $this->kb->ingest(
            projectId: (string) $row->project_id,
            sourceType: 'page',
            sourceId: (int) $row->getKey(),
            locale: $row->locale,
            content: $body,
        );
    }

    public function handleArticlePublished(ArticlePublished $event): void
    {
        $row = Article::query()->find($event->aggregateId());
        if ($row === null) {
            return;
        }

        $this->kb->ingest(
            projectId: $event->projectId,
            sourceType: 'article',
            sourceId: (int) $row->getKey(),
            locale: $row->locale,
            content: $row->body,
            topic: $row->title,
        );
    }
}
