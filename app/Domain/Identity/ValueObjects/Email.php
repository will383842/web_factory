<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidEmailException;

/**
 * Validated email address value object.
 */
final class Email
{
    public function __construct(public readonly string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailException("Invalid email address: {$value}");
        }
    }

    public function domain(): string
    {
        $parts = explode('@', $this->value);

        return $parts[1];
    }

    public function equals(self $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
