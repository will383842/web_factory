<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutomationRequests\Tables;

use App\Models\AutomationRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AutomationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('first_name')->label('Name')
                    ->state(fn (AutomationRequest $r): string => $r->fullName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')->searchable(),
                TextColumn::make('company')->toggleable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        AutomationRequest::STATUS_NEW => 'warning',
                        AutomationRequest::STATUS_CONTACTED, AutomationRequest::STATUS_QUALIFIED => 'gray',
                        AutomationRequest::STATUS_WON => 'success',
                        AutomationRequest::STATUS_LOST => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('contacted_at')->dateTime()->placeholder('—')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    AutomationRequest::STATUS_NEW => 'New',
                    AutomationRequest::STATUS_CONTACTED => 'Contacted',
                    AutomationRequest::STATUS_QUALIFIED => 'Qualified',
                    AutomationRequest::STATUS_WON => 'Won',
                    AutomationRequest::STATUS_LOST => 'Lost',
                ]),
            ])
            ->recordActions([
                Action::make('mark_contacted')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->visible(fn (AutomationRequest $r): bool => $r->status === AutomationRequest::STATUS_NEW)
                    ->action(function (AutomationRequest $r): void {
                        $r->forceFill([
                            'status' => AutomationRequest::STATUS_CONTACTED,
                            'contacted_at' => now(),
                        ])->save();
                        Notification::make()->title('Marked as contacted')->success()->send();
                    }),
            ]);
    }
}
