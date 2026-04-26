<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                TextColumn::make('slug')->searchable()->limit(40),
                TextColumn::make('locale')->badge()->sortable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'scheduled' => 'Scheduled',
                    'published' => 'Published', 'archived' => 'Archived',
                ]),
                SelectFilter::make('type')->options([
                    'home' => 'Home', 'static' => 'Static', 'pricing' => 'Pricing',
                    'form' => 'Form', 'legal' => 'Legal', 'index' => 'Index',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'name')->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
