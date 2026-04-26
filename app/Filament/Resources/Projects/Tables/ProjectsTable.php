<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('locale')->badge()->toggleable(),
                TextColumn::make('owner.email')->label('Owner')->searchable()->toggleable(),
                TextColumn::make('virality_score')->label('Virality')->numeric()->sortable()->toggleable(),
                TextColumn::make('value_score')->label('Value')->numeric()->sortable()->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'analyzing' => 'Analyzing',
                        'blueprinting' => 'Blueprinting',
                        'designing' => 'Designing',
                        'building' => 'Building',
                        'deployed' => 'Deployed',
                        'archived' => 'Archived',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
