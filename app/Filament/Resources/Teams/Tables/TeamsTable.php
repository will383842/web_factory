<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.email')->label('Owner')->searchable(),
                TextColumn::make('project.name')->label('Project')->placeholder('—')->toggleable(),
                TextColumn::make('memberships_count')->label('Members')->counts('memberships'),
                TextColumn::make('created_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
