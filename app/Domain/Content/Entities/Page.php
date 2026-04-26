<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\Events\PagePublished;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

final class Page extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly Slug $slug,
        public readonly Locale $locale,
        public readonly string $title,
    ) {}

    public static function publish(string $id, Slug $slug, Locale $locale, string $title): self
    {
        $page = new self($id, $slug, $locale, $title);
        $page->recordEvent(new PagePublished($id, $slug, $locale));

        return $page;
    }

    public static function rehydrate(string $id, Slug $slug, Locale $locale, string $title): self
    {
        return new self($id, $slug, $locale, $title);
    }
}
