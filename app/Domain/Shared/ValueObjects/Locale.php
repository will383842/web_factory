<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\InvalidLocaleException;

/**
 * BCP-47 / IETF language tag value object (e.g., "fr", "en-US", "fr-FR-Paris").
 *
 * Accepts the simplified WebFactory format `lang[-REGION[-CITY]]`:
 *  - lang: ISO-639 lower-case 2-3 letters
 *  - region (optional): ISO-3166 upper-case 2 letters
 *  - city (optional): free-form ASCII word, used by the audience-context engine
 */
final class Locale
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^[a-z]{2,3}(-[A-Z]{2}(-[A-Za-z][A-Za-z0-9]+)?)?$/', $value)) {
            throw new InvalidLocaleException("Invalid locale tag: {$value}");
        }
    }

    public function language(): string
    {
        return explode('-', $this->value)[0];
    }

    public function region(): ?string
    {
        $parts = explode('-', $this->value);

        return $parts[1] ?? null;
    }

    public function city(): ?string
    {
        $parts = explode('-', $this->value);

        return $parts[2] ?? null;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
