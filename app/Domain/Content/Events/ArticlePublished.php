<?php

declare(strict_types=1);

namespace App\Domain\Content\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

final class ArticlePublished extends DomainEvent
{
    public function __construct(
        public readonly string $articleId,
        public readonly string $projectId,
        public readonly Slug $slug,
        public readonly Locale $locale,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->articleId;
    }

    public function eventName(): string
    {
        return 'content.article.published';
    }
}
