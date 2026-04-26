<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows\Tables;

use App\Models\OnboardingFlow;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OnboardingFlowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('audience')->badge(),
                TextColumn::make('steps_count')
                    ->label('Steps')
                    ->state(fn (OnboardingFlow $r): int => count($r->steps->toArray())),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('progress_count')->counts('progress')->label('Users')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('audience')->options([
                    OnboardingFlow::AUDIENCE_USER => 'End-user',
                    OnboardingFlow::AUDIENCE_ADMIN => 'Admin',
                    OnboardingFlow::AUDIENCE_TEAM_OWNER => 'Team owner',
                ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
