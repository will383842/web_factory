<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ConversionNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'marketing.conversion.not_found';
    }
}
