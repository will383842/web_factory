<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons\Tables;

use App\Models\BillingCoupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BillingCouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->toggleable(),
                TextColumn::make('project.name')->label('Project')->placeholder('platform-wide')->toggleable(),
                TextColumn::make('discount')
                    ->state(fn (BillingCoupon $record): string => $record->percent_off !== null
                        ? ($record->percent_off.'%')
                        : (number_format(($record->amount_off ?? 0) / 100, 2).' '.($record->currency ?? '')),
                    ),
                TextColumn::make('duration')->badge()->sortable(),
                TextColumn::make('redeemed_count')->numeric()->sortable(),
                TextColumn::make('max_redemptions')->numeric()->placeholder('∞')->toggleable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('expires_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('duration')->options([
                    BillingCoupon::DURATION_ONCE => 'Once',
                    BillingCoupon::DURATION_REPEATING => 'Repeating',
                    BillingCoupon::DURATION_FOREVER => 'Forever',
                ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
