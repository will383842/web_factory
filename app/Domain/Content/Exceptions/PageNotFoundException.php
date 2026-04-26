<?php

declare(strict_types=1);

namespace App\Domain\Content\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PageNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'content.page.not_found';
    }
}
