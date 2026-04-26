<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons\Pages;

use App\Filament\Resources\BillingCoupons\BillingCouponResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingCoupon extends CreateRecord
{
    protected static string $resource = BillingCouponResource::class;
}
