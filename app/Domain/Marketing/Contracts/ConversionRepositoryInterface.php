<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Contracts;

use App\Domain\Marketing\Entities\Conversion;

interface ConversionRepositoryInterface
{
    public function findById(string $id): ?Conversion;

    public function save(Conversion $conversion): void;
}
