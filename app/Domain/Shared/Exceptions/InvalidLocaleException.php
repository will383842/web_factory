<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidLocaleException extends DomainException
{
    public function errorCode(): string
    {
        return 'shared.locale.invalid';
    }
}
