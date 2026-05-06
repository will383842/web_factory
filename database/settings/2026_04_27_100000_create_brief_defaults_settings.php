<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Initial values come from the markdown templates checked into the
        // repo. Editing the rendered settings later via Filament does NOT
        // touch the source files — re-running this migration would reset
        // the values, so we guard with `migrator->inGroup` checks.
        $base = base_path('resources/brief-defaults');

        $this->migrator->add('brief_defaults.injectionEnabled', true);
        $this->migrator->add('brief_defaults.design', $this->load($base.'/01-design.md'));
        $this->migrator->add('brief_defaults.production', $this->load($base.'/02-production.md'));
        $this->migrator->add('brief_defaults.accessibility', $this->load($base.'/03-accessibility.md'));
        $this->migrator->add('brief_defaults.seoAeo', $this->load($base.'/04-seo-aeo.md'));
        $this->migrator->add('brief_defaults.security', $this->load($base.'/05-security.md'));
    }

    private function load(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
};
