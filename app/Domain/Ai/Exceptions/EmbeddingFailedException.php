<?php

declare(strict_types=1);

namespace App\Domain\Ai\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class EmbeddingFailedException extends DomainException
{
    public function errorCode(): string
    {
        return 'ai.embedding.failed';
    }
}
