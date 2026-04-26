<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingInvoices\Tables;

use App\Models\BillingInvoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BillingInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('number')->searchable()->placeholder('—'),
                TextColumn::make('project.name')->label('Project')->toggleable(),
                TextColumn::make('customer.email')->label('Customer')->searchable(),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state, BillingInvoice $record): string => number_format($state / 100, 2).' '.$record->currency)
                    ->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        BillingInvoice::STATUS_PAID => 'success',
                        BillingInvoice::STATUS_OPEN => 'warning',
                        BillingInvoice::STATUS_VOID, BillingInvoice::STATUS_UNCOLLECTIBLE => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('paid_at')->dateTime()->toggleable(),
                TextColumn::make('due_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    BillingInvoice::STATUS_DRAFT => 'Draft',
                    BillingInvoice::STATUS_OPEN => 'Open',
                    BillingInvoice::STATUS_PAID => 'Paid',
                    BillingInvoice::STATUS_UNCOLLECTIBLE => 'Uncollectible',
                    BillingInvoice::STATUS_VOID => 'Void',
                ]),
            ])
            ->recordActions([
                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (BillingInvoice $record): ?string => $record->pdf_url, shouldOpenInNewTab: true)
                    ->visible(fn (BillingInvoice $record): bool => $record->pdf_url !== null),
            ]);
    }
}
