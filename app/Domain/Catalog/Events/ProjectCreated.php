<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Slug;

final class ProjectCreated extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly Slug $slug,
        public readonly string $name,
        public readonly string $ownerId,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.created';
    }
}
