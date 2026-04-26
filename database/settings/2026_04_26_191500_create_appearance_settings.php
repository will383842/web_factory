<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('appearance.colorPrimary', '#4F46E5');
        $this->migrator->add('appearance.colorSecondary', '#0F172A');
        $this->migrator->add('appearance.colorAccent', '#F59E0B');
        $this->migrator->add('appearance.colorBackground', '#FFFFFF');
        $this->migrator->add('appearance.colorForeground', '#0F172A');
        $this->migrator->add('appearance.fontHeading', 'Inter, system-ui, sans-serif');
        $this->migrator->add('appearance.fontBody', 'Inter, system-ui, sans-serif');
        $this->migrator->add('appearance.radiusSm', '0.25rem');
        $this->migrator->add('appearance.radiusMd', '0.5rem');
        $this->migrator->add('appearance.radiusLg', '1rem');
        $this->migrator->add('appearance.spacingUnit', '0.25rem');
    }
};
