<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows\Pages;

use App\Filament\Resources\OnboardingFlows\OnboardingFlowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOnboardingFlow extends CreateRecord
{
    protected static string $resource = OnboardingFlowResource::class;
}
