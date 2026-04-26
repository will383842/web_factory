<?php

declare(strict_types=1);

namespace App\Domain\Search\Entities;

use App\Domain\Search\Events\IndexUpdated;
use App\Domain\Shared\Entities\AggregateRoot;

final class SearchIndex extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $documentCount,
    ) {}

    public static function update(string $id, string $name, int $documentCount): self
    {
        $idx = new self($id, $name, $documentCount);
        $idx->recordEvent(new IndexUpdated($id, $name, $documentCount));

        return $idx;
    }

    public static function rehydrate(string $id, string $name, int $documentCount): self
    {
        return new self($id, $name, $documentCount);
    }
}
