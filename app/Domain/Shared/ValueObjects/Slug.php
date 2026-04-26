<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use App\Domain\Shared\Exceptions\InvalidSlugException;

/**
 * URL-safe slug — ASCII lower-case, dashes only, no leading/trailing dash.
 *
 * Per project policy (see auto-memory `feedback_slugs_ascii_only.md`), slugs
 * MUST be romanized ASCII; Unicode (AR/ZH/HI/RU) is rejected at this boundary.
 */
final class Slug
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new InvalidSlugException("Invalid slug: {$value}");
        }
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
