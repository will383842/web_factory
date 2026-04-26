<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Analytics\Events\MetricsRecorded;
use App\Domain\Shared\Entities\AggregateRoot;

final class MetricEvent extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly float $value,
    ) {}

    public static function record(string $id, string $name, float $value): self
    {
        $metric = new self($id, $name, $value);
        $metric->recordEvent(new MetricsRecorded($id, $name, $value));

        return $metric;
    }

    public static function rehydrate(string $id, string $name, float $value): self
    {
        return new self($id, $name, $value);
    }
}
