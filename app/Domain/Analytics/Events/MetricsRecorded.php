<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Events;

use App\Domain\Shared\Events\DomainEvent;

final class MetricsRecorded extends DomainEvent
{
    public function __construct(
        public readonly string $metricId,
        public readonly string $name,
        public readonly float $value,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->metricId;
    }

    public function eventName(): string
    {
        return 'analytics.metrics.recorded';
    }
}
