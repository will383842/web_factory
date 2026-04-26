<?php

declare(strict_types=1);

namespace App\Application\Marketing\DTOs;

use DateTimeImmutable;

final readonly class SitemapEntry
{
    /**
     * @param array<string, string> $alternates hreflang locale => URL
     */
    public function __construct(
        public string $url,
        public ?DateTimeImmutable $lastmod = null,
        public string $changefreq = 'weekly',
        public float $priority = 0.5,
        public array $alternates = [],
    ) {}
}
