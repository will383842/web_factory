<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Application\Marketing\DTOs\HreflangAlternate;

/**
 * Multi-locale hreflang link tag builder (Spec 14 — SEO/AEO 2026).
 *
 * Always emits an `x-default` alternate (using the first / canonical
 * locale) so search engines fall back consistently for unmapped users.
 */
final class HreflangBuilder
{
    /**
     * @param array<string, string> $localeUrlMap ["fr" => "https://x.com/fr/page", "en-US" => ...]
     *
     * @return list<HreflangAlternate>
     */
    public function build(array $localeUrlMap, ?string $xDefaultLocale = null): array
    {
        if ($localeUrlMap === []) {
            return [];
        }

        $alternates = [];
        foreach ($localeUrlMap as $locale => $url) {
            $alternates[] = new HreflangAlternate(locale: $locale, url: $url);
        }

        $xDefaultLocale ??= array_key_first($localeUrlMap);
        $alternates[] = new HreflangAlternate(
            locale: 'x-default',
            url: $localeUrlMap[$xDefaultLocale] ?? array_values($localeUrlMap)[0],
        );

        return $alternates;
    }

    /**
     * @param array<string, string> $localeUrlMap
     */
    public function renderHtml(array $localeUrlMap, ?string $xDefaultLocale = null): string
    {
        return implode(
            "\n",
            array_map(
                static fn (HreflangAlternate $a): string => $a->toLinkTag(),
                $this->build($localeUrlMap, $xDefaultLocale),
            ),
        );
    }
}
