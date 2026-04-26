<?php

declare(strict_types=1);

namespace App\Domain\Ai\Events;

use App\Domain\Shared\Events\DomainEvent;

final class EmbeddingGenerated extends DomainEvent
{
    public function __construct(
        public readonly string $chunkId,
        public readonly string $sourceUrl,
        public readonly int $dimensions,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->chunkId;
    }

    public function eventName(): string
    {
        return 'ai.embedding.generated';
    }
}
