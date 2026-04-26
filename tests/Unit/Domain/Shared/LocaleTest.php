<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\InvalidLocaleException;
use App\Domain\Shared\ValueObjects\Locale;

it('parses a plain language tag', function (): void {
    $loc = new Locale('fr');
    expect($loc->language())->toBe('fr')
        ->and($loc->region())->toBeNull()
        ->and($loc->city())->toBeNull();
});

it('parses lang-REGION', function (): void {
    $loc = new Locale('en-US');
    expect($loc->language())->toBe('en')
        ->and($loc->region())->toBe('US');
});

it('parses lang-REGION-City format used by audience-context engine', function (): void {
    $loc = new Locale('fr-FR-Paris');
    expect($loc->language())->toBe('fr')
        ->and($loc->region())->toBe('FR')
        ->and($loc->city())->toBe('Paris');
});

it('rejects malformed tags', function (): void {
    new Locale('not_a_locale');
})->throws(InvalidLocaleException::class);
