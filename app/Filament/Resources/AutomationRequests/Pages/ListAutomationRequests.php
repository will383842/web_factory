<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutomationRequests\Pages;

use App\Filament\Resources\AutomationRequests\AutomationRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListAutomationRequests extends ListRecords
{
    protected static string $resource = AutomationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
