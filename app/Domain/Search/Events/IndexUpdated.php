<?php

declare(strict_types=1);

namespace App\Domain\Search\Events;

use App\Domain\Shared\Events\DomainEvent;

final class IndexUpdated extends DomainEvent
{
    public function __construct(
        public readonly string $indexId,
        public readonly string $name,
        public readonly int $documentCount,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->indexId;
    }

    public function eventName(): string
    {
        return 'search.index.updated';
    }
}
