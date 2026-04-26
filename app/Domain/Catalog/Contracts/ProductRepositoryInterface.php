<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Shared\ValueObjects\Slug;

interface ProductRepositoryInterface
{
    public function findById(string $id): ?Product;

    public function findBySlug(Slug $slug): ?Product;

    public function save(Product $product): void;

    public function delete(string $id): void;
}
