<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/**
 * Marker base class for all domain-level exceptions.
 *
 * Inherits {@see RuntimeException} which is part of PHP core (not Illuminate),
 * preserving framework independence of the Domain layer.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Stable error code (e.g., `identity.email.invalid`) for clients/logs.
     */
    abstract public function errorCode(): string;
}
