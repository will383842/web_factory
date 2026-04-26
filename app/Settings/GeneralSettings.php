<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Global WebFactory settings — site-wide branding + feature toggles.
 *
 * Persisted via the {@see Settings} package (table
 * `settings`, group `general`). Cached through the default cache store so
 * reads cost nothing at runtime.
 */
final class GeneralSettings extends Settings
{
    public string $siteName;

    public string $siteTagline;

    public string $supportEmail;

    public string $defaultLocale;

    public bool $maintenanceMode;

    public static function group(): string
    {
        return 'general';
    }
}
