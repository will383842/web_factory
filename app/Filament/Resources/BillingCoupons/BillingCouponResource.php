<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons;

use App\Filament\Resources\BillingCoupons\Pages\CreateBillingCoupon;
use App\Filament\Resources\BillingCoupons\Pages\EditBillingCoupon;
use App\Filament\Resources\BillingCoupons\Pages\ListBillingCoupons;
use App\Filament\Resources\BillingCoupons\Schemas\BillingCouponForm;
use App\Filament\Resources\BillingCoupons\Tables\BillingCouponsTable;
use App\Models\BillingCoupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BillingCouponResource extends Resource
{
    protected static ?string $model = BillingCoupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Coupons';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return BillingCouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingCouponsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingCoupons::route('/'),
            'create' => CreateBillingCoupon::route('/create'),
            'edit' => EditBillingCoupon::route('/{record}/edit'),
        ];
    }
}
