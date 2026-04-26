<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserOnboardingProgress\Pages;

use App\Filament\Resources\UserOnboardingProgress\UserOnboardingProgressResource;
use Filament\Resources\Pages\ListRecords;

class ListUserOnboardingProgress extends ListRecords
{
    protected static string $resource = UserOnboardingProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
