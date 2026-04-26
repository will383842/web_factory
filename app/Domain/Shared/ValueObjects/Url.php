<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\InvalidUrlException;

/**
 * Immutable, validated absolute URL value object.
 */
final class Url
{
    public function __construct(public readonly string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidUrlException("Invalid URL: {$value}");
        }
        if (! preg_match('#^https?://#i', $value)) {
            throw new InvalidUrlException("URL must use http(s) scheme: {$value}");
        }
    }

    public function host(): string
    {
        return (string) parse_url($this->value, PHP_URL_HOST);
    }

    public function scheme(): string
    {
        return (string) parse_url($this->value, PHP_URL_SCHEME);
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
