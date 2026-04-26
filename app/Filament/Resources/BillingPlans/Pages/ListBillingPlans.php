<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPlans\Pages;

use App\Filament\Resources\BillingPlans\BillingPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingPlans extends ListRecords
{
    protected static string $resource = BillingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
