<?php

declare(strict_types=1);

namespace App\Domain\Search\Contracts;

use App\Domain\Search\Entities\SearchIndex;

interface SearchIndexRepositoryInterface
{
    public function findById(string $id): ?SearchIndex;

    public function findByName(string $name): ?SearchIndex;

    public function save(SearchIndex $index): void;
}
