<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;

final class DesignGenerated extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly int $mockupCount,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.design_generated';
    }
}
