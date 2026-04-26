<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Contracts;

use App\Domain\Analytics\Entities\MetricEvent;

interface MetricEventRepositoryInterface
{
    public function findById(string $id): ?MetricEvent;

    public function save(MetricEvent $metric): void;
}
