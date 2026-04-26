<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use App\Domain\Content\ValueObjects\ContentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Q&A')
                ->schema([
                    Select::make('project_id')->label('Project')
                        ->relationship('project', 'name')->searchable()->preload()->required(),
                    Select::make('locale')->required()->default('fr')->options([
                        'fr' => 'Français', 'en' => 'English', 'es' => 'Español',
                        'de' => 'Deutsch', 'it' => 'Italiano', 'pt' => 'Português',
                    ]),
                    Textarea::make('question')->required()->rows(2)->columnSpanFull()
                        ->placeholder('How does X work?'),
                    Textarea::make('answer')->required()->rows(6)->columnSpanFull()
                        ->placeholder('Short, direct answer (target ≤ 60 words for AEO).'),
                ])->columns(2),

            Section::make('Categorization & lifecycle')
                ->schema([
                    TextInput::make('category')->maxLength(64)->placeholder('billing | onboarding | troubleshooting'),
                    Toggle::make('is_featured')->label('Featured')->onColor('success'),
                    Select::make('status')->required()->default(ContentStatus::Draft->value)
                        ->options(collect(ContentStatus::cases())
                            ->mapWithKeys(fn (ContentStatus $s) => [$s->value => ucfirst($s->value)])
                            ->all()),
                ])->columns(3),

            Section::make('Engagement (read-only)')
                ->schema([
                    TextInput::make('view_count')->numeric()->disabled()->dehydrated()->default(0),
                    TextInput::make('helpful_count')->numeric()->disabled()->dehydrated()->default(0),
                ])->columns(2)->collapsed(),
        ]);
    }
}
