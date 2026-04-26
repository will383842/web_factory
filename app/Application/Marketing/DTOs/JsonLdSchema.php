<?php

declare(strict_types=1);

namespace App\Application\Marketing\DTOs;

/**
 * Marker DTO for a generated schema.org JSON-LD payload.
 *
 * The `data` array always carries the `@context` and `@type` keys at the
 * top level so it can be json_encode'd into a `<script type="application/ld+json">`.
 */
final readonly class JsonLdSchema
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $type,
        public array $data,
    ) {}

    public function toJson(): string
    {
        return (string) json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
