<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamInvitations;

use App\Filament\Resources\TeamInvitations\Pages\ListTeamInvitations;
use App\Filament\Resources\TeamInvitations\Tables\TeamInvitationsTable;
use App\Models\TeamInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TeamInvitationResource extends Resource
{
    protected static ?string $model = TeamInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Invitations';

    protected static string|UnitEnum|null $navigationGroup = 'Identity';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return TeamInvitationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamInvitations::route('/'),
        ];
    }
}
