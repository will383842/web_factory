<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\MoneyCurrencyMismatchException;

/**
 * Immutable monetary amount stored as integer minor units (cents) + ISO-4217
 * currency code, avoiding all floating-point arithmetic.
 */
final class Money
{
    public function __construct(
        public readonly int $amountMinor,
        public readonly Currency $currency,
    ) {}

    public static function fromMinor(int $amountMinor, string $currencyIso): self
    {
        return new self($amountMinor, Currency::fromIso($currencyIso));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amountMinor === 0;
    }

    public function isPositive(): bool
    {
        return $this->amountMinor > 0;
    }

    public function equals(self $other): bool
    {
        return $this->amountMinor === $other->amountMinor
            && $this->currency->equals($other->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->currency->equals($other->currency)) {
            throw new MoneyCurrencyMismatchException(
                "Cannot operate on amounts in different currencies: {$this->currency->iso} vs {$other->currency->iso}",
            );
        }
    }
}
