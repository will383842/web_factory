<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingCoupons\Schemas;

use App\Models\BillingCoupon;
use App\Models\Project;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingCouponForm
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
                TextInput::make('code')->required()->maxLength(60),
                TextInput::make('name')->maxLength(120),
                Toggle::make('is_active')->default(true),
            ])->columns(2),

            Section::make('Discount (set ONE of percent_off / amount_off)')->schema([
                TextInput::make('percent_off')->numeric()->minValue(1)->maxValue(100)->suffix('%'),
                TextInput::make('amount_off')->numeric()->minValue(0)->suffix('cents'),
                TextInput::make('currency')->length(3)->placeholder('EUR'),
            ])->columns(3),

            Section::make('Duration & limits')->schema([
                Select::make('duration')
                    ->required()
                    ->default(BillingCoupon::DURATION_ONCE)
                    ->options([
                        BillingCoupon::DURATION_ONCE => 'Once',
                        BillingCoupon::DURATION_REPEATING => 'Repeating',
                        BillingCoupon::DURATION_FOREVER => 'Forever',
                    ]),
                TextInput::make('duration_in_months')->numeric()->minValue(1)->maxValue(60),
                TextInput::make('max_redemptions')->numeric()->minValue(1),
                DateTimePicker::make('expires_at'),
            ])->columns(2),

            Section::make('Provider sync (Stripe)')->schema([
                TextInput::make('stripe_coupon_id')->maxLength(80),
            ])->collapsible(),
        ]);
    }
}
