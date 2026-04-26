<?php

declare(strict_types=1);

namespace App\Filament\Resources\SsoIdentities\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SsoIdentitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('provider')->badge()->sortable(),
                TextColumn::make('email')->label('Provider email')->toggleable(),
                TextColumn::make('expires_at')->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('provider')->options([
                    'google' => 'Google',
                    'microsoft' => 'Microsoft',
                    'apple' => 'Apple',
                    'okta' => 'Okta',
                    'github' => 'GitHub',
                ]),
            ])
            ->recordActions([DeleteAction::make()]);
    }
}
