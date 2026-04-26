<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingSubscriptions\Tables;

use App\Application\Billing\Services\BillingGateway;
use App\Models\BillingSubscription;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BillingSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('project.name')->label('Project')->toggleable(),
                TextColumn::make('customer.email')->label('Customer')->searchable(),
                TextColumn::make('plan.name')->label('Plan')->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BillingSubscription::STATUS_ACTIVE, BillingSubscription::STATUS_TRIALING => 'success',
                        BillingSubscription::STATUS_PAST_DUE, BillingSubscription::STATUS_UNPAID => 'warning',
                        BillingSubscription::STATUS_CANCELED, BillingSubscription::STATUS_INCOMPLETE_EXPIRED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('cancel_at_period_end')->boolean()->label('Cancel @ end')->toggleable(),
                TextColumn::make('current_period_end')->dateTime()->sortable(),
                TextColumn::make('trial_ends_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('canceled_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    BillingSubscription::STATUS_TRIALING => 'Trialing',
                    BillingSubscription::STATUS_ACTIVE => 'Active',
                    BillingSubscription::STATUS_PAST_DUE => 'Past due',
                    BillingSubscription::STATUS_CANCELED => 'Canceled',
                    BillingSubscription::STATUS_UNPAID => 'Unpaid',
                    BillingSubscription::STATUS_INCOMPLETE => 'Incomplete',
                    BillingSubscription::STATUS_INCOMPLETE_EXPIRED => 'Incomplete expired',
                ]),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel at period end')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BillingSubscription $record): bool => $record->isActive() && ! $record->cancel_at_period_end)
                    ->action(function (BillingSubscription $record): void {
                        app(BillingGateway::class)->cancelSubscription($record, atPeriodEnd: true);
                        Notification::make()->title('Subscription scheduled to cancel at period end')->success()->send();
                    }),
            ]);
    }
}
