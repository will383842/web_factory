<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Pages\UploadBrief;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    /**
     * Sprint 31 — replaced the default CreateAction (which led to the
     * obsolete 5-step wizard producing empty stubs) with a direct link to
     * the Upload-brief workflow, which is the only valid entry point for
     * creating a project in the local-Claude-Code workflow.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_brief')
                ->label('+ Upload brief')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->url(fn (): string => UploadBrief::getUrl()),
        ];
    }
}
