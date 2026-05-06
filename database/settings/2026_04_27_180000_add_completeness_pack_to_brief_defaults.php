<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Sprint 30 — completeness pack: 5 new preambles ensuring every project
        // ships with the expected pages, components, content engine, auth flows
        // and notification templates regardless of how detailed the brief is.
        $base = base_path('resources/brief-defaults');

        $this->migrator->add('brief_defaults.pagesLayout', $this->load($base.'/06-pages-layout.md'));
        $this->migrator->add('brief_defaults.components', $this->load($base.'/07-components.md'));
        $this->migrator->add('brief_defaults.contentEngine', $this->load($base.'/08-content-engine.md'));
        $this->migrator->add('brief_defaults.authAccount', $this->load($base.'/09-auth-account.md'));
        $this->migrator->add('brief_defaults.comms', $this->load($base.'/10-comms.md'));
    }

    private function load(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
};
