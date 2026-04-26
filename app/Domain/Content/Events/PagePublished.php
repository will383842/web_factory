<?php

declare(strict_types=1);

namespace App\Domain\Content\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

final class PagePublished extends DomainEvent
{
    public function __construct(
        public readonly string $pageId,
        public readonly Slug $slug,
        public readonly Locale $locale,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->pageId;
    }

    public function eventName(): string
    {
        return 'content.page.published';
    }
}
