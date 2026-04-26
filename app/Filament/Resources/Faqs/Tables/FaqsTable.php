<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                TextColumn::make('question')->searchable()->limit(60),
                TextColumn::make('locale')->badge(),
                TextColumn::make('category')->badge()->placeholder('—'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('view_count')->numeric()->label('Views')->toggleable(),
                TextColumn::make('helpful_count')->numeric()->label('Helpful')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'scheduled' => 'Scheduled',
                    'published' => 'Published', 'archived' => 'Archived',
                ]),
                TernaryFilter::make('is_featured')->label('Featured only'),
                SelectFilter::make('project_id')->relationship('project', 'name')->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
