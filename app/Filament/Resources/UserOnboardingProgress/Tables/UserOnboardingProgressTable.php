<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserOnboardingProgress\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserOnboardingProgressTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('flow.name')->label('Flow')->sortable(),
                TextColumn::make('score')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->sortable()
                    ->suffix('%'),
                TextColumn::make('started_at')->dateTime()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('—')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('completed')
                    ->query(fn (Builder $q): Builder => $q->whereNotNull('completed_at'))
                    ->toggle(),
            ]);
    }
}
