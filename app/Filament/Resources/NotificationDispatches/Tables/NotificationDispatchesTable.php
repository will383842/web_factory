<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationDispatches\Tables;

use App\Models\NotificationDispatch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationDispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable()->placeholder('—'),
                TextColumn::make('event_type')->searchable()->sortable(),
                TextColumn::make('channel')->badge()->sortable(),
                TextColumn::make('recipient')->limit(40)->toggleable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        NotificationDispatch::STATUS_SENT, NotificationDispatch::STATUS_DELIVERED => 'success',
                        NotificationDispatch::STATUS_QUEUED => 'gray',
                        NotificationDispatch::STATUS_SKIPPED => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('external_id')->limit(20)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sent_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    NotificationDispatch::STATUS_QUEUED => 'Queued',
                    NotificationDispatch::STATUS_SENT => 'Sent',
                    NotificationDispatch::STATUS_DELIVERED => 'Delivered',
                    NotificationDispatch::STATUS_FAILED => 'Failed',
                    NotificationDispatch::STATUS_BOUNCED => 'Bounced',
                    NotificationDispatch::STATUS_SKIPPED => 'Skipped',
                ]),
                SelectFilter::make('channel')->options([
                    'in_app' => 'In-app', 'email' => 'Email', 'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp', 'push_web' => 'Web push',
                    'push_mob' => 'Mobile push', 'telegram' => 'Telegram',
                    'slack' => 'Slack', 'discord' => 'Discord',
                ]),
            ]);
    }
}
