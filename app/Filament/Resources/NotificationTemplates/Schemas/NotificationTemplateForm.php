<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationTemplates\Schemas;

use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationTemplateForm
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
                TextInput::make('event_type')->required()->placeholder('billing.subscription.started')->maxLength(120),
                Select::make('channel')
                    ->required()
                    ->options([
                        'in_app' => 'In-app',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'push_web' => 'Web push (VAPID)',
                        'push_mob' => 'Mobile push',
                        'telegram' => 'Telegram',
                        'slack' => 'Slack',
                        'discord' => 'Discord',
                    ]),
                TextInput::make('locale')->required()->default('en')->length(2)->maxLength(12),
                Toggle::make('is_active')->default(true),
            ])->columns(2),

            Section::make('Content')->schema([
                TextInput::make('subject')->maxLength(255)->helperText('Optional. Email/SMS only.'),
                Textarea::make('body')->required()->rows(8)->helperText('Use {{ var }} placeholders.'),
            ]),
        ]);
    }
}
