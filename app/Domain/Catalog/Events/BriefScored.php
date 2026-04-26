<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;

final class BriefScored extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly int $score,
        public readonly bool $passes,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.brief_scored';
    }
}
