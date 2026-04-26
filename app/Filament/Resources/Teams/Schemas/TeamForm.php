<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teams\Schemas;

use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                Select::make('owner_id')
                    ->label('Owner')
                    ->required()
                    ->options(fn (): array => User::query()->orderBy('email')->pluck('email', 'id')->all())
                    ->searchable(),
                Select::make('project_id')
                    ->label('Project (optional)')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                TextInput::make('slug')->required()->maxLength(140),
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('logo_url')->url()->maxLength(500),
            ])->columns(2),

            Section::make('Settings')->schema([
                KeyValue::make('settings')->keyLabel('Key')->valueLabel('Value'),
            ]),
        ]);
    }
}
