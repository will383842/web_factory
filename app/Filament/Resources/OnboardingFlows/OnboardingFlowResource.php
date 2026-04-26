<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingFlows;

use App\Filament\Resources\OnboardingFlows\Pages\CreateOnboardingFlow;
use App\Filament\Resources\OnboardingFlows\Pages\EditOnboardingFlow;
use App\Filament\Resources\OnboardingFlows\Pages\ListOnboardingFlows;
use App\Filament\Resources\OnboardingFlows\Schemas\OnboardingFlowForm;
use App\Filament\Resources\OnboardingFlows\Tables\OnboardingFlowsTable;
use App\Models\OnboardingFlow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OnboardingFlowResource extends Resource
{
    protected static ?string $model = OnboardingFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Onboarding flows';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return OnboardingFlowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnboardingFlowsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardingFlows::route('/'),
            'create' => CreateOnboardingFlow::route('/create'),
            'edit' => EditOnboardingFlow::route('/{record}/edit'),
        ];
    }
}
