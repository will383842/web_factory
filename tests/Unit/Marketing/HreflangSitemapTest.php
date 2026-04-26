<?php

declare(strict_types=1);

use App\Application\Marketing\DTOs\SitemapEntry;
use App\Application\Marketing\Services\HreflangBuilder;
use App\Application\Marketing\Services\SitemapBuilder;
use DateTimeImmutable;

it('builds hreflang alternates with x-default fallback', function (): void {
    $alternates = (new HreflangBuilder)->build([
        'fr' => 'https://x.com/fr/page',
        'en-US' => 'https://x.com/en/page',
    ]);

    expect($alternates)->toHaveCount(3)
        ->and($alternates[0]->locale)->toBe('fr')
        ->and(end($alternates)->locale)->toBe('x-default')
        ->and(end($alternates)->url)->toBe('https://x.com/fr/page');
});

it('renders hreflang HTML link tags', function (): void {
    $html = (new HreflangBuilder)->renderHtml(['fr' => 'https://x.com/fr', 'en' => 'https://x.com/en']);
    expect($html)->toContain('hreflang="fr"')
        ->and($html)->toContain('hreflang="en"')
        ->and($html)->toContain('hreflang="x-default"');
});

it('emits empty when no locales given', function (): void {
    expect((new HreflangBuilder)->build([]))->toBe([]);
});

it('builds a valid sitemap.xml with xhtml:link alternates', function (): void {
    $entries = [
        new SitemapEntry(
            url: 'https://x.com/fr/page',
            lastmod: new DateTimeImmutable('2026-04-26T10:00:00+00:00'),
            changefreq: 'daily',
            priority: 0.8,
            alternates: ['fr' => 'https://x.com/fr/page', 'en' => 'https://x.com/en/page'],
        ),
    ];

    $xml = (new SitemapBuilder)->buildXml($entries);

    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($xml)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')
        ->and($xml)->toContain('<loc>https://x.com/fr/page</loc>')
        ->and($xml)->toContain('<lastmod>2026-04-26T10:00:00+00:00</lastmod>')
        ->and($xml)->toContain('<changefreq>daily</changefreq>')
        ->and($xml)->toContain('<priority>0.8</priority>')
        ->and($xml)->toContain('xhtml:link rel="alternate" hreflang="fr"')
        ->and($xml)->toContain('xhtml:link rel="alternate" hreflang="en"');
});
