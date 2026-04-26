<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPlans\Pages;

use App\Filament\Resources\BillingPlans\BillingPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingPlan extends EditRecord
{
    protected static string $resource = BillingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
