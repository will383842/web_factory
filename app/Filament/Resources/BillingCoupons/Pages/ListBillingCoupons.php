<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons\Pages;

use App\Filament\Resources\BillingCoupons\BillingCouponResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingCoupons extends ListRecords
{
    protected static string $resource = BillingCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
