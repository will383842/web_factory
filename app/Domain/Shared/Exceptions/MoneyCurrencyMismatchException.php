<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class MoneyCurrencyMismatchException extends DomainException
{
    public function errorCode(): string
    {
        return 'shared.money.currency_mismatch';
    }
}
