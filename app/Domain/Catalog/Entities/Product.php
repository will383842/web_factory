<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entities;

use App\Domain\Catalog\Contracts\ProductRepositoryInterface;
use App\Domain\Catalog\Events\ProductCreated;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Slug;

/**
 * Catalog aggregate root.
 *
 * Demonstrates the Sprint-1 reference pattern: factory `create()` records a
 * domain event; rehydration `rehydrate()` does NOT. Persistence is delegated
 * to {@see ProductRepositoryInterface}.
 */
final class Product extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly Slug $slug,
        public readonly string $name,
        public readonly Money $price,
    ) {}

    public static function create(string $id, Slug $slug, string $name, Money $price): self
    {
        $product = new self($id, $slug, $name, $price);
        $product->recordEvent(new ProductCreated($id, $slug, $name));

        return $product;
    }

    public static function rehydrate(string $id, Slug $slug, string $name, Money $price): self
    {
        return new self($id, $slug, $name, $price);
    }
}
