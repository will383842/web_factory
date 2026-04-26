<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationDispatches;

use App\Filament\Resources\NotificationDispatches\Pages\ListNotificationDispatches;
use App\Filament\Resources\NotificationDispatches\Tables\NotificationDispatchesTable;
use App\Models\NotificationDispatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NotificationDispatchResource extends Resource
{
    protected static ?string $model = NotificationDispatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Dispatch log';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 61;

    public static function table(Table $table): Table
    {
        return NotificationDispatchesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationDispatches::route('/'),
        ];
    }
}
