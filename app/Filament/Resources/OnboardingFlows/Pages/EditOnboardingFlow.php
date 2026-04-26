<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows\Pages;

use App\Filament\Resources\OnboardingFlows\OnboardingFlowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingFlow extends EditRecord
{
    protected static string $resource = OnboardingFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
