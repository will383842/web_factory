<?php

declare(strict_types=1);

namespace App\Application\Marketing\DTOs;

final readonly class HreflangAlternate
{
    public function __construct(
        public string $locale,   // e.g., "fr", "en-US", "x-default"
        public string $url,
    ) {}

    public function toLinkTag(): string
    {
        return sprintf(
            '<link rel="alternate" hreflang="%s" href="%s" />',
            htmlspecialchars($this->locale, ENT_QUOTES),
            htmlspecialchars($this->url, ENT_QUOTES),
        );
    }
}
