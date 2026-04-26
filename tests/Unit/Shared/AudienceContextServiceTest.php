<?php

declare(strict_types=1);

use App\Application\Shared\Services\AudienceContextService;
use App\Domain\Shared\ValueObjects\Locale;

beforeEach(function (): void {
    $this->svc = new AudienceContextService;
});

it('resolves fr-FR to BNP Paribas + EUR', function (): void {
    $ctx = $this->svc->resolve(new Locale('fr-FR'));
    expect($ctx->primaryBank)->toBe('BNP Paribas')
        ->and($ctx->currency)->toBe('EUR')
        ->and($ctx->countryCode)->toBe('FR')
        ->and($ctx->popularCities)->toContain('Paris');
});

it('resolves fr-CA to Desjardins + CAD (different from fr-FR)', function (): void {
    $ctx = $this->svc->resolve(new Locale('fr-CA'));
    expect($ctx->primaryBank)->toBe('Desjardins')
        ->and($ctx->currency)->toBe('CAD')
        ->and($ctx->countryCode)->toBe('CA');
});

it('resolves ar-MA to Attijariwafa Bank + MAD', function (): void {
    $ctx = $this->svc->resolve(new Locale('ar-MA'));
    expect($ctx->primaryBank)->toBe('Attijariwafa Bank')
        ->and($ctx->currency)->toBe('MAD');
});

it('resolves en-IN to State Bank of India + INR', function (): void {
    $ctx = $this->svc->resolve(new Locale('en-IN'));
    expect($ctx->primaryBank)->toBe('State Bank of India')
        ->and($ctx->currency)->toBe('INR');
});

it('falls back to en-US when locale is unknown', function (): void {
    $ctx = $this->svc->resolve(new Locale('xx-YY'));
    expect($ctx->locale)->toBe('en-US')
        ->and($ctx->primaryBank)->toBe('Chase');
});

it('falls back to same-language any-region when exact match missing', function (): void {
    // "fr" alone → finds fr-FR (first defined)
    $ctx = $this->svc->resolve(new Locale('fr'));
    expect($ctx->locale)->toBe('fr-FR');
});

it('exposes the supported locales list', function (): void {
    $locales = $this->svc->supportedLocales();
    expect($locales)->toContain('fr-FR', 'fr-CA', 'en-US', 'ar-MA', 'pt-BR', 'zh-CN');
});

it('AudienceContext::toArray() roundtrips all fields', function (): void {
    $arr = $this->svc->resolve(new Locale('de-DE'))->toArray();
    expect($arr)->toHaveKeys([
        'locale', 'country_code', 'country_name', 'currency',
        'primary_bank', 'popular_cities', 'local_competitors',
        'date_format', 'phone_format',
    ]);
});
