<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class MetricNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'analytics.metric.not_found';
    }
}
