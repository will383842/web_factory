<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPlans\Schemas;

use App\Models\BillingPlan;
use App\Models\Project;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                Select::make('project_id')
                    ->label('Project (leave empty for platform-wide)')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->placeholder('Platform-wide'),
                TextInput::make('slug')->required()->maxLength(120),
                TextInput::make('name')->required()->maxLength(150),
                Textarea::make('description')->rows(3)->maxLength(1000),
            ])->columns(2),

            Section::make('Pricing')->schema([
                TextInput::make('price_cents')
                    ->label('Price (cents)')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('currency')
                    ->required()
                    ->default('EUR')
                    ->length(3),
                Select::make('billing_cycle')
                    ->required()
                    ->default(BillingPlan::CYCLE_MONTHLY)
                    ->options([
                        BillingPlan::CYCLE_MONTHLY => 'Monthly',
                        BillingPlan::CYCLE_YEARLY => 'Yearly',
                        BillingPlan::CYCLE_ONE_TIME => 'One-time',
                    ]),
                Toggle::make('is_active')->default(true),
            ])->columns(2),

            Section::make('Features')->schema([
                KeyValue::make('features')
                    ->keyLabel('Feature')
                    ->valueLabel('Limit / value')
                    ->reorderable(),
            ]),

            Section::make('Provider sync (Stripe)')->schema([
                TextInput::make('stripe_product_id')->maxLength(80),
                TextInput::make('stripe_price_id')->maxLength(80),
            ])->columns(2)->collapsible(),
        ]);
    }
}
