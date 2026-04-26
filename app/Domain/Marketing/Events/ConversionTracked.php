<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Events;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Money;

final class ConversionTracked extends DomainEvent
{
    public function __construct(
        public readonly string $conversionId,
        public readonly string $source,
        public readonly Money $value,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->conversionId;
    }

    public function eventName(): string
    {
        return 'marketing.conversion.tracked';
    }
}
