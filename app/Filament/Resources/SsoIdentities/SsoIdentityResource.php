<?php

declare(strict_types=1);

namespace App\Filament\Resources\SsoIdentities;

use App\Filament\Resources\SsoIdentities\Pages\ListSsoIdentities;
use App\Filament\Resources\SsoIdentities\Tables\SsoIdentitiesTable;
use App\Models\SsoIdentity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SsoIdentityResource extends Resource
{
    protected static ?string $model = SsoIdentity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'SSO identities';

    protected static string|UnitEnum|null $navigationGroup = 'Identity';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return SsoIdentitiesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSsoIdentities::route('/'),
        ];
    }
}
