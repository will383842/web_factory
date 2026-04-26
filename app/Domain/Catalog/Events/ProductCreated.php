<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Slug;

final class ProductCreated extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly Slug $slug,
        public readonly string $name,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->productId;
    }

    public function eventName(): string
    {
        return 'catalog.product.created';
    }
}
