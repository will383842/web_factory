<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use App\Domain\Content\ValueObjects\ContentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')
                ->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(191)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->helperText('ASCII lowercase, dashes only.'),
                    Select::make('locale')
                        ->required()
                        ->default('fr')
                        ->options([
                            'fr' => 'Français', 'en' => 'English', 'es' => 'Español',
                            'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português',
                            'ar' => 'العربية', 'hi' => 'हिन्दी', 'zh' => '中文',
                        ]),
                ])->columns(2),

            Section::make('Type & status')
                ->schema([
                    Select::make('type')
                        ->required()
                        ->default('static')
                        ->options([
                            'home' => 'Home', 'static' => 'Static', 'pricing' => 'Pricing',
                            'form' => 'Form', 'legal' => 'Legal', 'index' => 'Index',
                        ]),
                    Select::make('status')
                        ->required()
                        ->default(ContentStatus::Draft->value)
                        ->options(collect(ContentStatus::cases())
                            ->mapWithKeys(fn (ContentStatus $s) => [$s->value => ucfirst($s->value)])
                            ->all()),
                    DateTimePicker::make('published_at'),
                ])->columns(3),

            Section::make('Content')
                ->schema([
                    Textarea::make('content_blocks')
                        ->label('Content blocks (JSON)')
                        ->rows(8)
                        ->helperText('Array of block objects (e.g., [{"text":"...", "type":"hero"}]).')
                        ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                        ->formatStateUsing(fn ($state) => is_array($state) || is_object($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? '[]')),
                    KeyValue::make('meta_tags')
                        ->keyLabel('Meta name')
                        ->valueLabel('Content'),
                ]),
        ]);
    }
}
