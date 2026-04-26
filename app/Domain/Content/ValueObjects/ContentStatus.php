<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObjects;

/**
 * Lifecycle states shared by Page / Article / FAQ.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
