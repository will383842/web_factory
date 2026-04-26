<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPlans\Pages;

use App\Filament\Resources\BillingPlans\BillingPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingPlan extends CreateRecord
{
    protected static string $resource = BillingPlanResource::class;
}
