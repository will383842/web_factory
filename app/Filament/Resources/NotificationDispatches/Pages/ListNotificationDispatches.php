<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationDispatches\Pages;

use App\Filament\Resources\NotificationDispatches\NotificationDispatchResource;
use Filament\Resources\Pages\ListRecords;

class ListNotificationDispatches extends ListRecords
{
    protected static string $resource = NotificationDispatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
