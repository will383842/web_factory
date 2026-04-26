<?php

declare(strict_types=1);

namespace App\Domain\Search\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class IndexNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'search.index.not_found';
    }
}
