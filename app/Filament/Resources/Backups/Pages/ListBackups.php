<?php

declare(strict_types=1);

namespace App\Filament\Resources\Backups\Pages;

use App\Application\Operations\Services\BackupRunner;
use App\Filament\Resources\Backups\BackupResource;
use App\Models\Backup as BackupModel;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_backup')
                ->label('Run backup')
                ->icon('heroicon-o-play-circle')
                ->color('primary')
                ->schema(fn (Schema $schema): Schema => $schema->components([
                    Select::make('kind')
                        ->label('Kind')
                        ->required()
                        ->default(BackupModel::KIND_FULL)
                        ->options([
                            BackupModel::KIND_FULL => 'Full',
                            BackupModel::KIND_INCREMENTAL => 'Incremental',
                            BackupModel::KIND_SNAPSHOT => 'Snapshot',
                        ]),
                    Select::make('project_id')
                        ->label('Project (leave empty for platform-wide)')
                        ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->placeholder('Platform-wide'),
                ]))
                ->action(function (array $data): void {
                    $projectId = isset($data['project_id']) && $data['project_id'] !== ''
                        ? (int) $data['project_id']
                        : null;

                    $backup = app(BackupRunner::class)->run(
                        kind: (string) $data['kind'],
                        projectId: $projectId,
                    );

                    if ($backup->status === BackupModel::STATUS_SUCCEEDED) {
                        Notification::make()
                            ->title('Backup succeeded')
                            ->body(sprintf('Archive: %s (%d bytes)', $backup->archive_path ?? '-', $backup->size_bytes ?? 0))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Backup failed')
                            ->body($backup->error_message ?? 'See logs')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
