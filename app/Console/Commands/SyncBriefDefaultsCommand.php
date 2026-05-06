<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Settings\BriefDefaultsSettings;
use Illuminate\Console\Command;

/**
 * Sprint 30.1 — sync the brief-default markdown files in
 * `resources/brief-defaults/` into the persisted Settings.
 *
 * Use case: you edit a `.md` file in your editor (e.g. clarifying that
 * marketing header/footer should NOT appear on /dashboard) and want to
 * push that change into the live settings without going through the
 * Filament admin and copy-pasting 10 textareas.
 *
 * Idempotent — running it twice is a no-op.
 */
final class SyncBriefDefaultsCommand extends Command
{
    protected $signature = 'webfactory:sync-brief-defaults {--dry-run : Show what would change without saving}';

    protected $description = 'Sync resources/brief-defaults/0X-*.md into BriefDefaultsSettings';

    /**
     * @var array<string, string> map setting key → relative file
     */
    private const FILE_MAP = [
        'design' => '01-design.md',
        'production' => '02-production.md',
        'accessibility' => '03-accessibility.md',
        'seoAeo' => '04-seo-aeo.md',
        'security' => '05-security.md',
        'pagesLayout' => '06-pages-layout.md',
        'components' => '07-components.md',
        'contentEngine' => '08-content-engine.md',
        'authAccount' => '09-auth-account.md',
        'comms' => '10-comms.md',
    ];

    public function handle(BriefDefaultsSettings $settings): int
    {
        $base = base_path('resources/brief-defaults');
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $missing = [];

        foreach (self::FILE_MAP as $key => $rel) {
            $path = $base.'/'.$rel;
            if (! is_file($path) || ! is_readable($path)) {
                $missing[] = $rel;

                continue;
            }

            $newValue = (string) file_get_contents($path);
            $oldValue = (string) ($settings->{$key} ?? '');

            if ($oldValue === $newValue) {
                $this->line("  ✓ {$key} (unchanged, ".strlen($newValue).' bytes)');

                continue;
            }

            $delta = strlen($newValue) - strlen($oldValue);
            $sign = $delta >= 0 ? '+' : '';
            $this->line("  → {$key}: {$sign}{$delta} bytes (".strlen($oldValue).' → '.strlen($newValue).')');

            if (! $dry) {
                $settings->{$key} = $newValue;
            }
            $changed++;
        }

        if ($missing !== []) {
            $this->warn('Missing source files: '.implode(', ', $missing));
        }

        if ($changed === 0) {
            $this->info('All preambles already in sync. Nothing to do.');

            return self::SUCCESS;
        }

        if ($dry) {
            $this->warn("Dry-run: {$changed} preamble(s) would be updated. Re-run without --dry-run to apply.");

            return self::SUCCESS;
        }

        $settings->save();
        $this->info("✓ Synced {$changed} preamble(s) into BriefDefaultsSettings.");
        $this->line('Next briefs uploaded will pick up the new content. Already-extracted workspaces are unaffected — re-upload to refresh.');

        return self::SUCCESS;
    }
}
