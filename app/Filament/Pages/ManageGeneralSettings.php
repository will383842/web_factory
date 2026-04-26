<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Filament admin page that edits {@see GeneralSettings} (site name, tagline,
 * support email, default locale, maintenance mode).
 *
 * Persists through Spatie's database settings repository; reads are cached.
 */
final class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'General settings';

    protected static ?string $title = 'General settings';

    protected static ?int $navigationSort = 99;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Branding')
                ->description('Public-facing identity of the platform.')
                ->schema([
                    TextInput::make('siteName')
                        ->label('Site name')
                        ->required()
                        ->maxLength(80),
                    TextInput::make('siteTagline')
                        ->label('Tagline')
                        ->maxLength(160),
                ]),
            Section::make('Contact & locale')
                ->schema([
                    TextInput::make('supportEmail')
                        ->label('Support email')
                        ->email()
                        ->required(),
                    TextInput::make('defaultLocale')
                        ->label('Default locale (BCP-47)')
                        ->required()
                        ->helperText('e.g. fr, en-US, fr-FR-Paris')
                        ->maxLength(15),
                ]),
            Section::make('Operations')
                ->schema([
                    Toggle::make('maintenanceMode')
                        ->label('Maintenance mode')
                        ->helperText('When ON, public traffic sees a maintenance page.')
                        ->onColor('danger'),
                ]),
        ]);
    }
}
