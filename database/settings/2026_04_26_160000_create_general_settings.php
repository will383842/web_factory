<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.siteName', 'WebFactory');
        $this->migrator->add('general.siteTagline', 'Plateformes web livrées en 24-48h');
        $this->migrator->add('general.supportEmail', 'support@webfactory.local');
        $this->migrator->add('general.defaultLocale', 'fr');
        $this->migrator->add('general.maintenanceMode', false);
    }
};
