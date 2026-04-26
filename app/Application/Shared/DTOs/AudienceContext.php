<?php

declare(strict_types=1);

namespace App\Application\Shared\DTOs;

/**
 * Locale-aware audience context — gives the content/SEO/AEO generators the
 * local references that make a generated platform feel native to its target
 * country (Spec 04 §AudienceContextService, used by SOS Expat platforms
 * since 2026-04-13).
 *
 * Examples:
 *   fr-FR → primaryBank="BNP Paribas",       currency="EUR"
 *   fr-CA → primaryBank="Desjardins",        currency="CAD"
 *   ar-MA → primaryBank="Attijariwafa Bank", currency="MAD"
 *   en-IN → primaryBank="State Bank of India", currency="INR"
 */
final readonly class AudienceContext
{
    /**
     * @param list<string> $popularCities
     * @param list<string> $localCompetitors
     */
    public function __construct(
        public string $locale,
        public string $countryCode,
        public string $countryName,
        public string $currency,
        public string $primaryBank,
        public array $popularCities,
        public array $localCompetitors,
        public string $dateFormat,
        public string $phoneFormat,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'currency' => $this->currency,
            'primary_bank' => $this->primaryBank,
            'popular_cities' => $this->popularCities,
            'local_competitors' => $this->localCompetitors,
            'date_format' => $this->dateFormat,
            'phone_format' => $this->phoneFormat,
        ];
    }
}
