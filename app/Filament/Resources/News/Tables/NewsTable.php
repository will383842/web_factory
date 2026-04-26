<?php

declare(strict_types=1);

namespace App\Filament\Resources\News\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('locale')->badge(),
                TextColumn::make('category')->badge()->placeholder('—'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('expires_at')->dateTime()->sortable()->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'scheduled' => 'Scheduled',
                    'published' => 'Published', 'archived' => 'Archived',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'name')->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
