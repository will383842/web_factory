<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\AppearanceSettings;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Filament admin page for {@see AppearanceSettings} — color palette,
 * typography, radii, spacing. The exposed tokens are consumed by the
 * generated platforms' Tailwind config so a brand override here cascades
 * everywhere.
 */
final class ManageAppearanceSettings extends SettingsPage
{
    protected static string $settings = AppearanceSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Appearance';

    protected static string|UnitEnum|null $navigationGroup = 'Design';

    protected static ?string $title = 'Appearance';

    protected static ?int $navigationSort = 50;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Palette')
                ->schema([
                    ColorPicker::make('colorPrimary')->label('Primary')->required(),
                    ColorPicker::make('colorSecondary')->label('Secondary')->required(),
                    ColorPicker::make('colorAccent')->label('Accent')->required(),
                    ColorPicker::make('colorBackground')->label('Background')->required(),
                    ColorPicker::make('colorForeground')->label('Foreground')->required(),
                ])->columns(3),

            Section::make('Typography')
                ->schema([
                    TextInput::make('fontHeading')->label('Heading font stack')->required()
                        ->placeholder('Inter, system-ui, sans-serif'),
                    TextInput::make('fontBody')->label('Body font stack')->required()
                        ->placeholder('Inter, system-ui, sans-serif'),
                ])->columns(2),

            Section::make('Radii & spacing')
                ->schema([
                    TextInput::make('radiusSm')->label('Radius sm')->required()->placeholder('0.25rem'),
                    TextInput::make('radiusMd')->label('Radius md')->required()->placeholder('0.5rem'),
                    TextInput::make('radiusLg')->label('Radius lg')->required()->placeholder('1rem'),
                    TextInput::make('spacingUnit')->label('Spacing unit')->required()->placeholder('0.25rem'),
                ])->columns(4),
        ]);
    }
}
