<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InvalidSlugException extends DomainException
{
    public function errorCode(): string
    {
        return 'shared.slug.invalid';
    }
}
