<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Application\Catalog\Services\BriefImporter;
use App\Models\Project;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

/**
 * Sprint 27 — Brief upload entry point.
 *
 * Workflow (single screen, single click):
 *   1. Developer creates an empty GitHub repo by hand and copies its URL.
 *   2. Drops the brief ZIP (CLAUDE.md + docs/) into the form.
 *   3. Hits "Create project" — WebFactory:
 *        - Creates the Project aggregate via CreateProjectHandler
 *        - Extracts the ZIP into {host_path}/{slug}/ via BriefImporter
 *        - Persists workspace + parsed CLAUDE.md under metadata.brief
 *        - Persists the GitHub URL under metadata.github
 *      and redirects to the Project edit page where the host path can be
 *      copied into a terminal.
 *
 * @property Schema $form
 */
final class UploadBrief extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Upload brief';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $title = 'Upload brief — start a new project';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.upload-brief';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Project identity')
                    ->description('Slug becomes the workspace folder name and must be unique.')
                    ->schema([
                        TextInput::make('slug')
                            ->label('Slug (folder name)')
                            ->required()
                            ->placeholder('avocat-ia')
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages([
                                'regex' => 'Lowercase ASCII alphanumeric and dashes only — no spaces, no dots.',
                            ])
                            ->unique(table: 'projects', column: 'slug')
                            ->maxLength(80),
                        TextInput::make('name')
                            ->label('Project name')
                            ->required()
                            ->placeholder('Avocat-IA')
                            ->maxLength(191),
                        TextInput::make('locale')
                            ->label('Primary locale (BCP-47)')
                            ->required()
                            ->default('fr-FR')
                            ->placeholder('fr-FR')
                            ->maxLength(15),
                        Textarea::make('description')
                            ->label('Short description')
                            ->rows(2)
                            ->maxLength(5000),
                    ])
                    ->columns(2),

                Section::make('GitHub repository')
                    ->description('Create the empty repo by hand on github.com first, then paste its URL here. WebFactory will push the generated code to it later.')
                    ->schema([
                        TextInput::make('github_url')
                            ->label('GitHub repo URL')
                            ->placeholder('https://github.com/will383842/avocat-ia.git')
                            ->url()
                            ->maxLength(500),
                    ]),

                Section::make('Brief archive')
                    ->description('A ZIP containing CLAUDE.md at the root and an optional docs/ subfolder. Max 50 MB.')
                    ->schema([
                        FileUpload::make('brief_zip')
                            ->label('Brief ZIP')
                            ->required()
                            ->disk('local')
                            ->directory('brief-uploads')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                            ->maxSize(50 * 1024) // 50 MB
                            ->helperText('Tip: include a `docs/` folder with your detailed spec docs.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $payload = $this->form->getState();

        $userId = (string) (auth()->id() ?? 0);
        if ($userId === '0') {
            Notification::make()
                ->title('Authentication required')
                ->danger()
                ->send();

            return;
        }

        $zipRelative = is_array($payload['brief_zip'] ?? null)
            ? (string) array_values($payload['brief_zip'])[0]
            : (string) $payload['brief_zip'];
        $zipAbsolute = Storage::disk('local')->path($zipRelative);

        try {
            DB::transaction(function () use ($payload, $userId, $zipAbsolute): void {
                /** @var CreateProjectHandler $handler */
                $handler = app(CreateProjectHandler::class);
                $domainProject = $handler->handle(new CreateProjectCommand(
                    slug: (string) $payload['slug'],
                    name: (string) $payload['name'],
                    description: (string) ($payload['description'] ?? ''),
                    locale: (string) $payload['locale'],
                    primaryDomain: null,
                    ownerId: $userId,
                    metadata: [],
                ));

                /** @var BriefImporter $importer */
                $importer = app(BriefImporter::class);
                $result = $importer->import($zipAbsolute, (string) $payload['slug']);

                /** @var Project $row */
                $row = Project::query()->findOrFail($domainProject->id);

                $githubUrl = trim((string) ($payload['github_url'] ?? ''));
                $githubMeta = $githubUrl !== ''
                    ? [
                        'html_url' => $githubUrl,
                        'full_name' => self::extractFullNameFromUrl($githubUrl),
                        'default_branch' => 'main',
                        'pushed_at' => null,
                    ]
                    : [];

                $row->metadata = array_merge((array) $row->metadata, [
                    'brief' => $result->toMetadataArray(),
                    'github' => $githubMeta,
                ]);
                $row->save();

                $this->redirect(
                    route('filament.admin.resources.projects.edit', ['record' => $row->getKey()]),
                );
            });

            Notification::make()
                ->title('Project created and brief extracted')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Brief import failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private static function extractFullNameFromUrl(string $url): string
    {
        // Accepts https://github.com/owner/repo or https://github.com/owner/repo.git
        if (preg_match('#github\.com[:/]([^/]+/[^/]+?)(?:\.git)?(?:/?)$#i', $url, $m) === 1) {
            return $m[1];
        }

        return '';
    }
}
