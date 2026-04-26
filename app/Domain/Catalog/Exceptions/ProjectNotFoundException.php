<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ProjectNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'catalog.project.not_found';
    }
}
