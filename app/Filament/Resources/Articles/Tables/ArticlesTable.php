<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')->badge(),
                IconColumn::make('is_pillar')->boolean()->label('Pillar'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('quality_score')->numeric()->sortable()->label('Quality'),
                TextColumn::make('word_count')->numeric()->label('Words')->toggleable(),
                TextColumn::make('reading_time_minutes')->label('Min')->toggleable(),
                TextColumn::make('published_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'scheduled' => 'Scheduled',
                    'published' => 'Published', 'archived' => 'Archived',
                ]),
                TernaryFilter::make('is_pillar')->label('Pillar only')
                    ->trueLabel('Pillar')->falseLabel('Standard'),
                SelectFilter::make('project_id')->relationship('project', 'name')->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
