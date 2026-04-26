<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows\Pages;

use App\Filament\Resources\OnboardingFlows\OnboardingFlowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingFlows extends ListRecords
{
    protected static string $resource = OnboardingFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
