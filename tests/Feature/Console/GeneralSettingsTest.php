<?php

declare(strict_types=1);

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads default values from the settings migration', function (): void {
    $settings = app(GeneralSettings::class);

    expect($settings->siteName)->toBe('WebFactory')
        ->and($settings->defaultLocale)->toBe('fr')
        ->and($settings->maintenanceMode)->toBeFalse();
});

it('persists changes through save() and reloads them', function (): void {
    $settings = app(GeneralSettings::class);
    $settings->siteName = 'My Custom Site';
    $settings->maintenanceMode = true;
    $settings->save();

    // Re-resolve from the container to bypass any in-memory state
    app()->forgetInstance(GeneralSettings::class);
    $reloaded = app(GeneralSettings::class);

    expect($reloaded->siteName)->toBe('My Custom Site')
        ->and($reloaded->maintenanceMode)->toBeTrue();
});

it('exposes "general" as the settings group name', function (): void {
    expect(GeneralSettings::group())->toBe('general');
});
