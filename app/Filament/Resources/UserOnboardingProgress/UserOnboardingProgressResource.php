<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserOnboardingProgress;

use App\Filament\Resources\UserOnboardingProgress\Pages\ListUserOnboardingProgress;
use App\Filament\Resources\UserOnboardingProgress\Tables\UserOnboardingProgressTable;
use App\Models\UserOnboardingProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserOnboardingProgressResource extends Resource
{
    protected static ?string $model = UserOnboardingProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Onboarding progress';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 51;

    public static function table(Table $table): Table
    {
        return UserOnboardingProgressTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserOnboardingProgress::route('/'),
        ];
    }
}
