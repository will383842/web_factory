<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows\Schemas;

use App\Models\OnboardingFlow;
use App\Models\Project;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OnboardingFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                Select::make('project_id')
                    ->label('Project (leave empty for platform-wide)')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->placeholder('Platform-wide'),
                TextInput::make('slug')->required()->maxLength(120),
                TextInput::make('name')->required()->maxLength(150),
                Select::make('audience')
                    ->required()
                    ->default(OnboardingFlow::AUDIENCE_USER)
                    ->options([
                        OnboardingFlow::AUDIENCE_USER => 'End-user',
                        OnboardingFlow::AUDIENCE_ADMIN => 'Admin',
                        OnboardingFlow::AUDIENCE_TEAM_OWNER => 'Team owner',
                    ]),
                Toggle::make('is_active')->default(true),
            ])->columns(2),

            Section::make('Steps (ordered)')->schema([
                Repeater::make('steps')
                    ->label('Steps')
                    ->schema([
                        TextInput::make('key')->required()->maxLength(60),
                        TextInput::make('title')->required()->maxLength(200),
                        TextInput::make('weight')->numeric()->minValue(1)->default(1),
                        TextInput::make('cta_url')->url()->maxLength(500),
                        TextInput::make('icon')->maxLength(60),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->defaultItems(0),
            ]),
        ]);
    }
}
