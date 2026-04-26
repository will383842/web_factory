<?php

declare(strict_types=1);

namespace App\Domain\Marketing\Entities;

use App\Domain\Marketing\Events\ConversionTracked;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Money;

final class Conversion extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly Money $value,
    ) {}

    public static function track(string $id, string $source, Money $value): self
    {
        $conversion = new self($id, $source, $value);
        $conversion->recordEvent(new ConversionTracked($id, $source, $value));

        return $conversion;
    }

    public static function rehydrate(string $id, string $source, Money $value): self
    {
        return new self($id, $source, $value);
    }
}
