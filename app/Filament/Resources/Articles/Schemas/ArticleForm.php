<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Domain\Content\ValueObjects\ContentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')
                ->schema([
                    Select::make('project_id')->label('Project')
                        ->relationship('project', 'name')->searchable()->preload()->required(),
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->required()->maxLength(191)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                    Select::make('locale')->required()->default('fr')->options([
                        'fr' => 'Français', 'en' => 'English', 'es' => 'Español',
                        'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português',
                    ]),
                ])->columns(2),

            Section::make('Content')
                ->schema([
                    Textarea::make('excerpt')->rows(3)->maxLength(500)->columnSpanFull(),
                    Textarea::make('body')->required()->rows(15)->columnSpanFull()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set): void {
                            $words = str_word_count((string) $state);
                            $set('word_count', $words);
                            $set('reading_time_minutes', max(1, (int) ceil($words / 220)));
                        }),
                    TextInput::make('featured_image_url')->url()->maxLength(500),
                ]),

            Section::make('SEO & lifecycle')
                ->schema([
                    TagsInput::make('seo_keywords')->placeholder('Add keyword')->columnSpanFull(),
                    Toggle::make('is_pillar')->label('Pillar article')->onColor('success'),
                    Select::make('status')->required()->default(ContentStatus::Draft->value)
                        ->options(collect(ContentStatus::cases())
                            ->mapWithKeys(fn (ContentStatus $s) => [$s->value => ucfirst($s->value)])
                            ->all()),
                    DateTimePicker::make('published_at'),
                ])->columns(3),

            Section::make('Stats (computed)')
                ->schema([
                    TextInput::make('word_count')->numeric()->default(0)->disabled()->dehydrated(),
                    TextInput::make('reading_time_minutes')->label('Reading min')->numeric()->default(0)->disabled()->dehydrated(),
                    TextInput::make('quality_score')->numeric()->minValue(0)->maxValue(100)->default(0)
                        ->helperText('0-100. Auto-publish gate >= 80.'),
                ])->columns(3),
        ]);
    }
}
