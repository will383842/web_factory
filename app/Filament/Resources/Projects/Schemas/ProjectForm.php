<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * 5-step wizard form for the Project resource (Spec 30 — Sprint 4):
 *  1. Idea       — slug, name, description, locale
 *  2. Audience   — primary domain + locale-driven targeting
 *  3. Stack      — technical preferences captured in metadata
 *  4. Branding   — primary domain + extra metadata fields
 *  5. Review     — owner + status + scores
 */
class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Workspace verification')
                ->description('Sprint 28 — last run of the static structure check (header action "Verify workspace").')
                ->schema([
                    Placeholder::make('verification_summary')
                        ->label('')
                        ->content(static function (?Project $record): HtmlString {
                            $v = (array) ($record?->metadata['workspace_verification'] ?? []);
                            if ($v === []) {
                                return new HtmlString('<span class="text-gray-500">Not verified yet — click "Verify workspace" in the header.</span>');
                            }
                            $score = (int) ($v['score'] ?? 0);
                            $passed = (array) ($v['passed'] ?? []);
                            $failed = (array) ($v['failed'] ?? []);
                            $warnings = (array) ($v['warnings'] ?? []);
                            $color = $score >= 80 ? 'green' : ($score >= 50 ? 'amber' : 'red');
                            $emoji = $score >= 80 ? '✅' : ($score >= 50 ? '⚠️' : '❌');

                            $html = '<div class="space-y-3">';
                            $html .= '<div class="text-2xl font-bold text-'.$color.'-600">'.$emoji.' Score: '.$score.'/100</div>';
                            $html .= '<div class="text-xs text-gray-500">Verified at: '.e((string) ($v['verified_at'] ?? '')).'</div>';

                            if ($failed !== []) {
                                $html .= '<details open class="rounded-lg border border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30 p-3">';
                                $html .= '<summary class="cursor-pointer font-semibold text-red-700 dark:text-red-400">❌ Failed ('.count($failed).')</summary>';
                                $html .= '<ul class="mt-2 list-disc pl-5 text-sm space-y-1">';
                                foreach ($failed as $msg) {
                                    $html .= '<li>'.e((string) $msg).'</li>';
                                }
                                $html .= '</ul></details>';
                            }

                            if ($warnings !== []) {
                                $html .= '<details class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30 p-3">';
                                $html .= '<summary class="cursor-pointer font-semibold text-amber-700 dark:text-amber-400">⚠️ Warnings ('.count($warnings).')</summary>';
                                $html .= '<ul class="mt-2 list-disc pl-5 text-sm space-y-1">';
                                foreach ($warnings as $msg) {
                                    $html .= '<li>'.e((string) $msg).'</li>';
                                }
                                $html .= '</ul></details>';
                            }

                            if ($passed !== []) {
                                $html .= '<details class="rounded-lg border border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/30 p-3">';
                                $html .= '<summary class="cursor-pointer font-semibold text-green-700 dark:text-green-400">✅ Passed ('.count($passed).')</summary>';
                                $html .= '<ul class="mt-2 list-disc pl-5 text-sm space-y-1">';
                                foreach ($passed as $msg) {
                                    $html .= '<li>'.e((string) $msg).'</li>';
                                }
                                $html .= '</ul></details>';
                            }

                            $html .= '</div>';

                            return new HtmlString($html);
                        }),
                ])
                ->columnSpanFull()
                ->collapsible(),

            Section::make('Workspace & GitHub')
                ->description('Sprint 27 — paths and repo set when the brief was uploaded.')
                ->schema([
                    Placeholder::make('workspace_host_path')
                        ->label('Local path (host)')
                        ->content(static function (?Project $record): HtmlString {
                            $path = (string) ($record?->metadata['brief']['workspace_host_path'] ?? '');
                            if ($path === '') {
                                return new HtmlString('<span class="text-gray-500">No brief uploaded yet — go to the "Upload brief" page to start.</span>');
                            }

                            return new HtmlString('<code class="text-sm">'.e($path).'</code>');
                        }),
                    Placeholder::make('next_command')
                        ->label('Next step — open Claude Code on this workspace')
                        ->content(static function (?Project $record): HtmlString {
                            $path = (string) ($record?->metadata['brief']['workspace_host_path'] ?? '');
                            if ($path === '') {
                                return new HtmlString('<span class="text-gray-500">—</span>');
                            }

                            // VS Code accepts vscode://file/<absolute-path> on Windows.
                            // The path uses forward slashes; the leading slash before
                            // the drive letter is required by the URI scheme.
                            $vscodeUri = 'vscode://file/'.ltrim(str_replace('\\', '/', $path), '/');

                            // Inline styles instead of Tailwind classes — Filament v4
                            // strips unknown utility classes, so a Tailwind-flavored
                            // button rendered inside a Placeholder content shows up
                            // unstyled. Inline CSS bypasses that.
                            $btn = 'display:inline-flex;align-items:center;gap:0.5rem;background:#4f46e5;color:#fff;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;font-size:0.875rem;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.05);';
                            $hint = 'margin-left:0.75rem;font-size:0.75rem;color:#6b7280;';
                            $codeBox = 'display:block;background:#0f172a;color:#e2e8f0;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.85rem;font-family:ui-monospace,monospace;overflow-x:auto;margin-top:0.5rem;';

                            return new HtmlString(
                                '<div style="display:flex;flex-direction:column;gap:0.75rem;margin-top:0.5rem;">'
                                .'<div>'
                                .'<a href="'.e($vscodeUri).'" style="'.$btn.'">📝 Ouvrir ce dossier dans VS Code</a>'
                                .'<span style="'.$hint.'">puis dans VS Code : Ctrl+Shift+P → « Claude Code: Start »</span>'
                                .'</div>'
                                .'<div style="font-size:0.75rem;color:#6b7280;">Ou via terminal :</div>'
                                .'<code style="'.$codeBox.'">cd '.e($path).' &amp;&amp; claude</code>'
                                .'</div>',
                            );
                        }),
                    Placeholder::make('github_repo')
                        ->label('GitHub repo')
                        ->content(static function (?Project $record): HtmlString {
                            $url = (string) ($record?->metadata['github']['html_url'] ?? '');
                            $pushedAt = (string) ($record?->metadata['github']['pushed_at'] ?? '');
                            $lastPush = (array) ($record?->metadata['github']['last_push'] ?? []);
                            if ($url === '') {
                                return new HtmlString('<span class="text-gray-500">No GitHub URL provided at brief upload.</span>');
                            }

                            $html = '<div class="space-y-2">';
                            $html .= '<a href="'.e($url).'" target="_blank" class="underline">'.e($url).'</a>';
                            if ($pushedAt !== '') {
                                $html .= '<div class="text-xs text-green-700 dark:text-green-400">✅ Last successful push: '.e($pushedAt).'</div>';
                            }
                            if ($lastPush !== [] && empty($lastPush['success'])) {
                                $html .= '<div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30 p-3 text-sm">';
                                $html .= '<div class="font-semibold text-amber-700 dark:text-amber-400">⚠️ Last push attempt failed</div>';
                                if (! empty($lastPush['error_message'])) {
                                    $html .= '<div class="mt-1 text-xs text-amber-700 dark:text-amber-300">'.e((string) $lastPush['error_message']).'</div>';
                                }
                                if (! empty($lastPush['manual_command'])) {
                                    $html .= '<div class="mt-2 text-xs text-gray-600 dark:text-gray-400">Run this on your host terminal to finish:</div>';
                                    $html .= '<pre class="mt-1 rounded bg-gray-900 px-3 py-2 text-xs text-gray-100 overflow-x-auto">'.e((string) $lastPush['manual_command']).'</pre>';
                                }
                                $html .= '</div>';
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        }),
                    Placeholder::make('brief_summary')
                        ->label('Brief summary')
                        ->content(static function (?Project $record): HtmlString {
                            $brief = (array) ($record?->metadata['brief'] ?? []);
                            if ($brief === []) {
                                return new HtmlString('<span class="text-gray-500">—</span>');
                            }
                            $parsed = (array) ($brief['parsed_claude_md'] ?? []);
                            $stack = (array) ($parsed['detected_stack'] ?? []);
                            $title = (string) ($parsed['title'] ?? '(untitled)');
                            $bytes = (int) ($brief['total_bytes'] ?? 0);
                            $files = (int) ($brief['extracted_file_count'] ?? 0);
                            $docs = (int) ($brief['docs_file_count'] ?? 0);
                            $sections = (int) ($parsed['h2_section_count'] ?? 0);
                            $kb = number_format($bytes / 1024, 1);

                            return new HtmlString(
                                '<div class="text-sm space-y-1">'
                                .'<div><strong>Title:</strong> '.e($title).'</div>'
                                .'<div><strong>Files extracted:</strong> '.$files.' ('.$docs.' in <code>docs/</code>) — '.$kb.' KB</div>'
                                .'<div><strong>CLAUDE.md sections (H2):</strong> '.$sections.'</div>'
                                .'<div><strong>Detected stack:</strong> '.($stack === [] ? '—' : e(implode(', ', $stack))).'</div>'
                                .'</div>',
                            );
                        }),
                ])
                ->columnSpanFull()
                ->collapsible(),

            Wizard::make([
                Step::make('Idea')
                    ->description('What are we building?')
                    ->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(191)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->helperText('ASCII lowercase, dashes only (e.g. "my-saas-idea").'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191),
                        Textarea::make('description')
                            ->rows(4)
                            ->maxLength(2000),
                    ]),

                Step::make('Audience')
                    ->description('Locale & target domain')
                    ->schema([
                        TextInput::make('locale')
                            ->required()
                            ->default('fr')
                            ->maxLength(15)
                            ->regex('/^[a-z]{2,3}(-[A-Z]{2}(-[A-Za-z][A-Za-z0-9]+)?)?$/')
                            ->helperText('BCP-47 tag (e.g. fr, en-US, fr-FR-Paris).'),
                        TextInput::make('primary_domain')
                            ->label('Primary domain (optional)')
                            ->maxLength(191)
                            ->placeholder('example.com'),
                    ]),

                Step::make('Stack')
                    ->description('Technical preferences (free-form)')
                    ->schema([
                        KeyValue::make('metadata.stack')
                            ->label('Stack preferences')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Persisted under metadata.stack — accepted by the pipeline jobs.'),
                    ]),

                Step::make('Branding')
                    ->description('Branding hints')
                    ->schema([
                        KeyValue::make('metadata.branding')
                            ->label('Branding hints')
                            ->keyLabel('Key')
                            ->valueLabel('Value'),
                    ]),

                Step::make('Review')
                    ->description('Ownership & lifecycle')
                    ->schema([
                        Select::make('owner_id')
                            ->label('Owner')
                            ->required()
                            ->relationship('owner', 'email')
                            ->searchable()
                            ->preload()
                            ->default(fn () => User::query()->where('email', config('app.admin_email'))->value('id')),
                        Select::make('status')
                            ->required()
                            ->options(collect(ProjectStatus::cases())
                                ->mapWithKeys(fn (ProjectStatus $s): array => [$s->value => $s->label()])
                                ->all())
                            ->default(ProjectStatus::Draft->value),
                        TextInput::make('virality_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                        TextInput::make('value_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(),
        ]);
    }
}
