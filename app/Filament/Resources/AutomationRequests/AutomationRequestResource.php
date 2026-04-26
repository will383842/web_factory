<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutomationRequests;

use App\Filament\Resources\AutomationRequests\Pages\ListAutomationRequests;
use App\Filament\Resources\AutomationRequests\Tables\AutomationRequestsTable;
use App\Models\AutomationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AutomationRequestResource extends Resource
{
    protected static ?string $model = AutomationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Automation requests';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 70;

    public static function table(Table $table): Table
    {
        return AutomationRequestsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutomationRequests::route('/'),
        ];
    }
}
