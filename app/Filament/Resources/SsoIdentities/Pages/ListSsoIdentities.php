<?php

declare(strict_types=1);

namespace App\Filament\Resources\SsoIdentities\Pages;

use App\Filament\Resources\SsoIdentities\SsoIdentityResource;
use Filament\Resources\Pages\ListRecords;

class ListSsoIdentities extends ListRecords
{
    protected static string $resource = SsoIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
