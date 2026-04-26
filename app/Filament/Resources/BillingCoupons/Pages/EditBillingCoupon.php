<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons\Pages;

use App\Filament\Resources\BillingCoupons\BillingCouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingCoupon extends EditRecord
{
    protected static string $resource = BillingCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
