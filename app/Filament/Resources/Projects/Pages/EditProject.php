<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Application\Catalog\Services\WorkspaceGitPusher;
use App\Application\Catalog\Services\WorkspaceVerifier;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sprint 28 — run a static structure verification on the host
            // workspace for this project. Persists the report under
            // metadata.workspace_verification so the form section can
            // display passed / failed / warnings on next render.
            Action::make('verify_workspace')
                ->label('Verify workspace')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Run workspace verification?')
                ->modalDescription('Scans the project folder on disk and checks structure (CLAUDE.md, composer.json, tests/, CI files, …). Pure read-only.')
                ->action(function (): void {
                    /** @var Project $project */
                    $project = $this->record;
                    $path = (string) ($project->metadata['brief']['workspace_container_path'] ?? '');
                    if ($path === '') {
                        Notification::make()
                            ->title('No workspace path on this project')
                            ->body('Upload a brief first via "Upload brief".')
                            ->danger()
                            ->send();

                        return;
                    }

                    $report = app(WorkspaceVerifier::class)->verify($path);

                    $project->metadata = array_merge((array) $project->metadata, [
                        'workspace_verification' => $report->toMetadataArray(),
                    ]);
                    $project->save();

                    Notification::make()
                        ->title("Score: {$report->score}/100")
                        ->body($report->isReady()
                            ? 'Workspace looks production-ready.'
                            : 'Workspace has gaps — see the form below for details.')
                        ->{$report->isReady() ? 'success' : 'warning'}()
                        ->send();

                    $this->fillForm();
                }),

            // Sprint 29 — git init/add/commit/remote/push the workspace to
            // the GitHub URL provided at brief upload. Idempotent. Without a
            // WEBFACTORY_GH_TOKEN, prepares the repo and surfaces the
            // manual `git push` command for the developer to run on host.
            Action::make('push_to_github')
                ->label('Push to GitHub')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Push the generated code to GitHub?')
                ->modalDescription('Runs git init / add / commit / push against the workspace folder, using the GitHub URL you set at brief upload.')
                ->action(function (): void {
                    /** @var Project $project */
                    $project = $this->record;

                    $workspacePath = (string) ($project->metadata['brief']['workspace_container_path'] ?? '');
                    $hostPath = (string) ($project->metadata['brief']['workspace_host_path'] ?? '');
                    $githubUrl = (string) ($project->metadata['github']['html_url'] ?? '');

                    if ($workspacePath === '' || $githubUrl === '') {
                        Notification::make()
                            ->title('Missing workspace or GitHub URL')
                            ->body('Upload a brief with a GitHub repo URL first.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $result = app(WorkspaceGitPusher::class)->push($workspacePath, $githubUrl, $hostPath);

                    $githubMeta = (array) ($project->metadata['github'] ?? []);
                    $project->metadata = array_merge((array) $project->metadata, [
                        'github' => array_merge($githubMeta, [
                            'last_push' => $result->toMetadataArray(),
                            'pushed_at' => $result->success ? now()->toIso8601String() : ($githubMeta['pushed_at'] ?? null),
                        ]),
                    ]);
                    $project->save();

                    if ($result->success) {
                        Notification::make()
                            ->title('Pushed to GitHub')
                            ->body("Commit {$result->commitSha} on {$result->remoteUrl}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Push not completed')
                            ->body($result->errorMessage ?? 'See the form below for the manual command.')
                            ->warning()
                            ->persistent()
                            ->send();
                    }

                    $this->fillForm();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
