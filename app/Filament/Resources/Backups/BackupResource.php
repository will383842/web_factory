<?php

declare(strict_types=1);

namespace App\Filament\Resources\Backups;

use App\Filament\Resources\Backups\Pages\ListBackups;
use App\Filament\Resources\Backups\Tables\BackupsTable;
use App\Models\Backup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $navigationLabel = 'Backups';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 80;

    public static function canCreate(): bool
    {
        // Backups are created by the "Run backup" header action, not via a
        // form — disable the standard /create page so the UI stays focused.
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        // Backups are read-only audit rows, no edit form.
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return BackupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackups::route('/'),
        ];
    }
}
