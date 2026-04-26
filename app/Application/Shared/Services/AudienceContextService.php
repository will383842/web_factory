<?php

declare(strict_types=1);

namespace App\Application\Shared\Services;

use App\Application\Shared\DTOs\AudienceContext;
use App\Domain\Shared\ValueObjects\Locale;

/**
 * Resolves the local "feel" (bank, currency, city list, competitors, formats)
 * for a given locale. Used by every content generator to anchor copy in the
 * target country (Spec 04, mirrored from the SOS Expat AudienceContextService
 * 2026-04-13).
 *
 * Returns a default `Locale::language() == 'en'` US context when the locale
 * is unknown so generation never blocks.
 */
final class AudienceContextService
{
    /** @var array<string, array<string, mixed>> */
    private const CONTEXTS = [
        // FR-speaking
        'fr-FR' => [
            'country_code' => 'FR', 'country_name' => 'France',
            'currency' => 'EUR', 'primary_bank' => 'BNP Paribas',
            'popular_cities' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux'],
            'local_competitors' => ['LeBonCoin', 'Doctolib', 'BlaBlaCar'],
            'date_format' => 'd/m/Y', 'phone_format' => '+33 X XX XX XX XX',
        ],
        'fr-CA' => [
            'country_code' => 'CA', 'country_name' => 'Canada',
            'currency' => 'CAD', 'primary_bank' => 'Desjardins',
            'popular_cities' => ['Montréal', 'Québec', 'Laval', 'Gatineau'],
            'local_competitors' => ['Kijiji', 'LesPAC'],
            'date_format' => 'Y-m-d', 'phone_format' => '+1 XXX XXX XXXX',
        ],
        // EN-speaking
        'en-US' => [
            'country_code' => 'US', 'country_name' => 'United States',
            'currency' => 'USD', 'primary_bank' => 'Chase',
            'popular_cities' => ['New York', 'Los Angeles', 'Chicago', 'Houston'],
            'local_competitors' => ['Yelp', 'Craigslist', 'NextDoor'],
            'date_format' => 'm/d/Y', 'phone_format' => '+1 XXX-XXX-XXXX',
        ],
        'en-GB' => [
            'country_code' => 'GB', 'country_name' => 'United Kingdom',
            'currency' => 'GBP', 'primary_bank' => 'Barclays',
            'popular_cities' => ['London', 'Manchester', 'Birmingham', 'Leeds'],
            'local_competitors' => ['Gumtree', 'Rightmove'],
            'date_format' => 'd/m/Y', 'phone_format' => '+44 XXXX XXXXXX',
        ],
        'en-IN' => [
            'country_code' => 'IN', 'country_name' => 'India',
            'currency' => 'INR', 'primary_bank' => 'State Bank of India',
            'popular_cities' => ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad'],
            'local_competitors' => ['OLX', 'Quikr', 'NoBroker'],
            'date_format' => 'd-m-Y', 'phone_format' => '+91 XXXXX XXXXX',
        ],
        // ES
        'es-ES' => [
            'country_code' => 'ES', 'country_name' => 'España',
            'currency' => 'EUR', 'primary_bank' => 'Santander',
            'popular_cities' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla'],
            'local_competitors' => ['Wallapop', 'Idealista'],
            'date_format' => 'd/m/Y', 'phone_format' => '+34 XXX XX XX XX',
        ],
        'es-MX' => [
            'country_code' => 'MX', 'country_name' => 'México',
            'currency' => 'MXN', 'primary_bank' => 'Banamex',
            'popular_cities' => ['Ciudad de México', 'Guadalajara', 'Monterrey'],
            'local_competitors' => ['Mercado Libre', 'Segundamano'],
            'date_format' => 'd/m/Y', 'phone_format' => '+52 XX XXXX XXXX',
        ],
        // AR
        'ar-MA' => [
            'country_code' => 'MA', 'country_name' => 'Maroc',
            'currency' => 'MAD', 'primary_bank' => 'Attijariwafa Bank',
            'popular_cities' => ['Casablanca', 'Rabat', 'Marrakech', 'Fès'],
            'local_competitors' => ['Avito', 'Mubawab'],
            'date_format' => 'd/m/Y', 'phone_format' => '+212 X XX XX XX XX',
        ],
        'ar-SA' => [
            'country_code' => 'SA', 'country_name' => 'المملكة العربية السعودية',
            'currency' => 'SAR', 'primary_bank' => 'Al Rajhi Bank',
            'popular_cities' => ['الرياض', 'جدة', 'مكة المكرمة'],
            'local_competitors' => ['Haraj', 'OpenSooq'],
            'date_format' => 'd/m/Y', 'phone_format' => '+966 XX XXX XXXX',
        ],
        // HI
        'hi-IN' => [
            'country_code' => 'IN', 'country_name' => 'भारत',
            'currency' => 'INR', 'primary_bank' => 'HDFC Bank',
            'popular_cities' => ['मुंबई', 'दिल्ली', 'बेंगलुरु'],
            'local_competitors' => ['OLX', 'Quikr'],
            'date_format' => 'd-m-Y', 'phone_format' => '+91 XXXXX XXXXX',
        ],
        // PT
        'pt-BR' => [
            'country_code' => 'BR', 'country_name' => 'Brasil',
            'currency' => 'BRL', 'primary_bank' => 'Itaú',
            'popular_cities' => ['São Paulo', 'Rio de Janeiro', 'Brasília'],
            'local_competitors' => ['Mercado Livre', 'OLX BR'],
            'date_format' => 'd/m/Y', 'phone_format' => '+55 XX XXXXX-XXXX',
        ],
        // DE
        'de-DE' => [
            'country_code' => 'DE', 'country_name' => 'Deutschland',
            'currency' => 'EUR', 'primary_bank' => 'Deutsche Bank',
            'popular_cities' => ['Berlin', 'Hamburg', 'München', 'Köln'],
            'local_competitors' => ['eBay Kleinanzeigen', 'Mobile.de'],
            'date_format' => 'd.m.Y', 'phone_format' => '+49 XXX XXXXXXXX',
        ],
        // ZH
        'zh-CN' => [
            'country_code' => 'CN', 'country_name' => '中国',
            'currency' => 'CNY', 'primary_bank' => '中国工商银行',
            'popular_cities' => ['北京', '上海', '广州', '深圳'],
            'local_competitors' => ['58同城', '赶集网'],
            'date_format' => 'Y-m-d', 'phone_format' => '+86 XXX XXXX XXXX',
        ],
    ];

    /** Default fallback when the locale is unknown. */
    private const DEFAULT_KEY = 'en-US';

    public function resolve(Locale $locale): AudienceContext
    {
        $key = $this->matchKey($locale);
        $ctx = self::CONTEXTS[$key] ?? self::CONTEXTS[self::DEFAULT_KEY];

        return new AudienceContext(
            locale: $key,
            countryCode: (string) $ctx['country_code'],
            countryName: (string) $ctx['country_name'],
            currency: (string) $ctx['currency'],
            primaryBank: (string) $ctx['primary_bank'],
            popularCities: (array) $ctx['popular_cities'],
            localCompetitors: (array) $ctx['local_competitors'],
            dateFormat: (string) $ctx['date_format'],
            phoneFormat: (string) $ctx['phone_format'],
        );
    }

    private function matchKey(Locale $locale): string
    {
        $exact = $locale->language().($locale->region() ? '-'.$locale->region() : '');
        if (isset(self::CONTEXTS[$exact])) {
            return $exact;
        }

        // Fallback: same language, any region.
        foreach (array_keys(self::CONTEXTS) as $k) {
            if (str_starts_with($k, $locale->language().'-')) {
                return $k;
            }
        }

        return self::DEFAULT_KEY;
    }

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        return array_keys(self::CONTEXTS);
    }
}
