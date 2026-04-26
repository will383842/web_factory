<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamInvitations\Tables;

use App\Models\TeamInvitation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeamInvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('team.name')->label('Team')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TeamInvitation::STATUS_ACCEPTED => 'success',
                        TeamInvitation::STATUS_EXPIRED, TeamInvitation::STATUS_REVOKED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('inviter.email')->label('Invited by')->toggleable(),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                TextColumn::make('accepted_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    TeamInvitation::STATUS_PENDING => 'Pending',
                    TeamInvitation::STATUS_ACCEPTED => 'Accepted',
                    TeamInvitation::STATUS_REVOKED => 'Revoked',
                    TeamInvitation::STATUS_EXPIRED => 'Expired',
                ]),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TeamInvitation $record): bool => $record->status === TeamInvitation::STATUS_PENDING)
                    ->action(function (TeamInvitation $record): void {
                        $record->forceFill(['status' => TeamInvitation::STATUS_REVOKED])->save();
                        Notification::make()->title('Invitation revoked')->success()->send();
                    }),
            ]);
    }
}
