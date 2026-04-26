<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ComplianceViolationException extends DomainException
{
    public function errorCode(): string
    {
        return 'compliance.violation';
    }
}
