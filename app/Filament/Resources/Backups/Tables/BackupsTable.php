<?php

declare(strict_types=1);

namespace App\Filament\Resources\Backups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BackupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('project.name')->label('Project')->searchable()->placeholder('platform-wide')->toggleable(),
                TextColumn::make('kind')->badge()->sortable(),
                TextColumn::make('target')->badge()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('size_bytes')->numeric()->label('Size (B)')->toggleable(),
                TextColumn::make('archive_path')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('started_at')->dateTime()->sortable(),
                TextColumn::make('finished_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'running' => 'Running', 'succeeded' => 'Succeeded', 'failed' => 'Failed',
                ]),
                SelectFilter::make('kind')->options([
                    'full' => 'Full', 'incremental' => 'Incremental', 'snapshot' => 'Snapshot',
                ]),
                SelectFilter::make('target')->options([
                    'local' => 'Local', 'r2' => 'Cloudflare R2', 'b2' => 'Backblaze B2',
                    'gdrive' => 'Google Drive', 'borg' => 'BorgBackup',
                ]),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
