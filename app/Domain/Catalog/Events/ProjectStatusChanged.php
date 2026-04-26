<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Events\DomainEvent;

final class ProjectStatusChanged extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly ProjectStatus $from,
        public readonly ProjectStatus $to,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.status_changed';
    }
}
