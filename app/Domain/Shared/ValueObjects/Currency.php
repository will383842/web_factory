<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\InvalidCurrencyException;

/**
 * ISO-4217 currency code value object.
 */
final class Currency
{
    private function __construct(public readonly string $iso) {}

    public static function fromIso(string $iso): self
    {
        $iso = strtoupper(trim($iso));
        if (! preg_match('/^[A-Z]{3}$/', $iso)) {
            throw new InvalidCurrencyException("Invalid ISO-4217 currency code: {$iso}");
        }

        return new self($iso);
    }

    public function equals(self $other): bool
    {
        return $this->iso === $other->iso;
    }
}
