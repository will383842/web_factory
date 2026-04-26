<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingSubscriptions;

use App\Filament\Resources\BillingSubscriptions\Pages\ListBillingSubscriptions;
use App\Filament\Resources\BillingSubscriptions\Tables\BillingSubscriptionsTable;
use App\Models\BillingSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BillingSubscriptionResource extends Resource
{
    protected static ?string $model = BillingSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return BillingSubscriptionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingSubscriptions::route('/'),
        ];
    }
}
