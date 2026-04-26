<?php

declare(strict_types=1);

namespace App\Filament\Resources\News\Schemas;

use App\Domain\Content\ValueObjects\ContentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                Select::make('project_id')->label('Project')
                    ->relationship('project', 'name')->searchable()->preload()->required(),
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(191)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                Select::make('locale')->required()->default('fr')->options([
                    'fr' => 'Français', 'en' => 'English', 'es' => 'Español',
                    'de' => 'Deutsch', 'pt' => 'Português', 'ar' => 'العربية',
                ]),
            ])->columns(2),

            Section::make('Content')->schema([
                Textarea::make('summary')->rows(3)->maxLength(500)->columnSpanFull(),
                Textarea::make('body')->required()->rows(8)->columnSpanFull(),
                TextInput::make('source_url')->label('Source URL')->url()->maxLength(500),
                TextInput::make('category')->maxLength(64)
                    ->placeholder('product | release | press | event'),
            ]),

            Section::make('Lifecycle')->schema([
                Select::make('status')->required()->default(ContentStatus::Draft->value)
                    ->options(collect(ContentStatus::cases())
                        ->mapWithKeys(fn (ContentStatus $s) => [$s->value => ucfirst($s->value)])
                        ->all()),
                DateTimePicker::make('published_at'),
                DateTimePicker::make('expires_at')
                    ->helperText('Auto-archived after this timestamp.'),
            ])->columns(3),
        ]);
    }
}
