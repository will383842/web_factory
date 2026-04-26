<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPlans\Tables;

use App\Models\BillingPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BillingPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('project.name')->label('Project')->placeholder('platform-wide')->toggleable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state, BillingPlan $record): string => number_format($state / 100, 2).' '.$record->currency)
                    ->sortable(),
                TextColumn::make('billing_cycle')->badge()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('subscriptions_count')
                    ->label('Subs')
                    ->counts('subscriptions')
                    ->toggleable(),
                TextColumn::make('updated_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('billing_cycle')->options([
                    BillingPlan::CYCLE_MONTHLY => 'Monthly',
                    BillingPlan::CYCLE_YEARLY => 'Yearly',
                    BillingPlan::CYCLE_ONE_TIME => 'One-time',
                ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
