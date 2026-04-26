<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Raised when the brief score gate (≥ 85) blocks the pipeline transition
 * from step 4 (build brief) to step 5 (init GitHub repo).
 */
final class BriefScoreTooLowException extends DomainException
{
    public function errorCode(): string
    {
        return 'catalog.project.brief_score_too_low';
    }
}
