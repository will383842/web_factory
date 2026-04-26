<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Models\User;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

/**
 * 5-step wizard form for the Project resource (Spec 30 — Sprint 4):
 *  1. Idea       — slug, name, description, locale
 *  2. Audience   — primary domain + locale-driven targeting
 *  3. Stack      — technical preferences captured in metadata
 *  4. Branding   — primary domain + extra metadata fields
 *  5. Review     — owner + status + scores
 */
class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Idea')
                    ->description('What are we building?')
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->helperText('ASCII lowercase, dashes only (e.g. "my-saas-idea").'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191),
                        Textarea::make('description')
                            ->rows(4)
                            ->maxLength(2000),
                    ]),

                Step::make('Audience')
                    ->description('Locale & target domain')
                    ->schema([
                        TextInput::make('locale')
                            ->required()
                            ->default('fr')
                            ->maxLength(15)
                            ->regex('/^[a-z]{2,3}(-[A-Z]{2}(-[A-Za-z][A-Za-z0-9]+)?)?$/')
                            ->helperText('BCP-47 tag (e.g. fr, en-US, fr-FR-Paris).'),
                        TextInput::make('primary_domain')
                            ->label('Primary domain (optional)')
                            ->maxLength(191)
                            ->placeholder('example.com'),
                    ]),

                Step::make('Stack')
                    ->description('Technical preferences (free-form)')
                    ->schema([
                        KeyValue::make('metadata.stack')
                            ->label('Stack preferences')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Persisted under metadata.stack — accepted by the pipeline jobs.'),
                    ]),

                Step::make('Branding')
                    ->description('Branding hints')
                    ->schema([
                        KeyValue::make('metadata.branding')
                            ->label('Branding hints')
                            ->keyLabel('Key')
                            ->valueLabel('Value'),
                    ]),

                Step::make('Review')
                    ->description('Ownership & lifecycle')
                    ->schema([
                        Select::make('owner_id')
                            ->label('Owner')
                            ->required()
                            ->relationship('owner', 'email')
                            ->searchable()
                            ->preload()
                            ->default(fn () => User::query()->where('email', config('app.admin_email'))->value('id')),
                        Select::make('status')
                            ->required()
                            ->options(collect(ProjectStatus::cases())
                                ->mapWithKeys(fn (ProjectStatus $s): array => [$s->value => $s->label()])
                                ->all())
                            ->default(ProjectStatus::Draft->value),
                        TextInput::make('virality_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                        TextInput::make('value_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(),
        ]);
    }
}
