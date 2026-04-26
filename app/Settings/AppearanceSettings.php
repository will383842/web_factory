<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Design-system tokens editable from the admin panel.
 *
 * Sprint 11 covers the foundational palette + typography. Subsequent
 * sprints will plug these into the generated platforms' Tailwind config.
 */
final class AppearanceSettings extends Settings
{
    public string $colorPrimary;

    public string $colorSecondary;

    public string $colorAccent;

    public string $colorBackground;

    public string $colorForeground;

    public string $fontHeading;

    public string $fontBody;

    public string $radiusSm;

    public string $radiusMd;

    public string $radiusLg;

    public string $spacingUnit;

    public static function group(): string
    {
        return 'appearance';
    }
}
