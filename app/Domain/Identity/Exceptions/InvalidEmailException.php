<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidEmailException extends DomainException
{
    public function errorCode(): string
    {
        return 'identity.email.invalid';
    }
}
