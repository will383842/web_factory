<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidUrlException extends DomainException
{
    public function errorCode(): string
    {
        return 'shared.url.invalid';
    }
}
