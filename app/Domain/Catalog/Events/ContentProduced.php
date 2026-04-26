<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Sprint 15 — emitted when pipeline step 6 finishes producing all content
 * (pages + articles + FAQs) across the project's locales. Listeners use this
 * to trigger the deploy step 7 (Sprint 16) and to ping IndexNow (Sprint 8).
 */
final class ContentProduced extends DomainEvent
{
    /**
     * @param list<string> $producedLocales
     */
    public function __construct(
        public readonly string $projectId,
        public readonly int $pagesCount,
        public readonly int $articlesCount,
        public readonly int $faqsCount,
        public readonly array $producedLocales,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.content_produced';
    }
}
