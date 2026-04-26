<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NotificationTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('event_type')->searchable()->sortable(),
                TextColumn::make('channel')->badge()->sortable(),
                TextColumn::make('locale')->sortable(),
                TextColumn::make('project.name')->label('Project')->placeholder('platform-wide')->toggleable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('channel')->options([
                    'in_app' => 'In-app', 'email' => 'Email', 'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp', 'push_web' => 'Web push',
                    'push_mob' => 'Mobile push', 'telegram' => 'Telegram',
                    'slack' => 'Slack', 'discord' => 'Discord',
                ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
