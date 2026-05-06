<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\BriefImportResult;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Sprint 27 — extract a developer-supplied brief ZIP to the host workspace
 * so Claude Code (the CLI, running natively on the host) can `cd` into the
 * directory and generate the project.
 *
 * The ZIP is expected to contain at least:
 *   - CLAUDE.md       → master spec read by Claude Code on every session
 *   - docs/*.{docx,md,txt} → detailed spec docs (optional)
 *
 * Idempotency: if a workspace already exists for the slug, the import fails
 * loudly rather than silently merging — protects in-progress generations.
 */
final class BriefImporter
{
    public function __construct(
        private readonly string $hostBasePath,
        private readonly string $containerBasePath,
        private readonly ?BriefDefaultsInjector $defaultsInjector = null,
    ) {}

    /**
     * Extract the ZIP into `{containerBasePath}/{slug}/` and parse CLAUDE.md.
     *
     * @throws RuntimeException on bad slug, existing workspace, corrupted zip,
     *                          or missing CLAUDE.md
     */
    public function import(string $zipAbsolutePath, string $slug): BriefImportResult
    {
        $this->assertSafeSlug($slug);

        if (! is_file($zipAbsolutePath) || ! is_readable($zipAbsolutePath)) {
            throw new RuntimeException("Brief ZIP not readable: {$zipAbsolutePath}");
        }

        $workspaceContainer = rtrim($this->containerBasePath, '/').'/'.$slug;
        $workspaceHost = rtrim($this->hostBasePath, '/\\').'/'.$slug;

        if (is_dir($workspaceContainer)) {
            throw new RuntimeException(
                "Workspace already exists at {$workspaceContainer}. ".
                'Delete it first or pick a different slug.',
            );
        }

        if (! @mkdir($workspaceContainer, 0o755, true) && ! is_dir($workspaceContainer)) {
            throw new RuntimeException("Could not create workspace at {$workspaceContainer}");
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipAbsolutePath);
        if ($opened !== true) {
            throw new RuntimeException("ZipArchive::open failed (code {$opened}) on {$zipAbsolutePath}");
        }

        // Manual extraction with backslash → slash normalization. Windows
        // PowerShell `Compress-Archive` produces ZIP entries with backslash
        // separators (e.g. `docs\file.docx`) which violates the spec; PHP
        // ZipArchive::extractTo treats them as literal filenames on Linux,
        // resulting in flat files named `docs\file.docx` instead of nested
        // `docs/file.docx`. We normalize on the fly to support both formats.
        $extractedCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }
            $rawName = (string) $stat['name'];
            $normalized = str_replace('\\', '/', $rawName);

            // Reject path traversal — refuse anything resolving outside the workspace.
            if (str_contains($normalized, '..')) {
                $zip->close();
                throw new RuntimeException("Refusing zip entry with parent reference: {$rawName}");
            }

            $target = $workspaceContainer.'/'.ltrim($normalized, '/');

            if (str_ends_with($normalized, '/')) {
                if (! is_dir($target) && ! @mkdir($target, 0o755, true) && ! is_dir($target)) {
                    $zip->close();
                    throw new RuntimeException("Could not create directory {$target}");
                }

                continue;
            }

            $parent = dirname($target);
            if (! is_dir($parent) && ! @mkdir($parent, 0o755, true) && ! is_dir($parent)) {
                $zip->close();
                throw new RuntimeException("Could not create parent directory {$parent}");
            }

            $stream = $zip->getStream($rawName);
            if ($stream === false) {
                $zip->close();
                throw new RuntimeException("Could not read zip entry {$rawName}");
            }
            $written = file_put_contents($target, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($written === false) {
                $zip->close();
                throw new RuntimeException("Could not write {$target}");
            }
            $extractedCount++;
        }
        $zip->close();

        // If the ZIP wrapped its content in a single top-level folder, hoist
        // it up so CLAUDE.md sits at the workspace root (Claude Code expects
        // it there). Common pattern when zipping from Windows Explorer.
        $this->flattenSingleTopLevelFolder($workspaceContainer);

        $claudeMdRelative = $this->findClaudeMd($workspaceContainer);

        // Sprint 28 — inject the design/production/a11y/SEO/security preambles
        // BEFORE we parse the file, so the parsed metadata reflects what
        // Claude Code will actually see when it opens the workspace.
        $defaultsInjected = false;
        if ($claudeMdRelative !== null && $this->defaultsInjector !== null) {
            $defaultsInjected = $this->defaultsInjector->augmentFile(
                $workspaceContainer.'/'.$claudeMdRelative,
            );
        }

        $parsed = [];
        if ($claudeMdRelative !== null) {
            $parsed = $this->parseClaudeMd($workspaceContainer.'/'.$claudeMdRelative);
            $parsed['defaults_injected'] = $defaultsInjected;
        }

        $docsDir = $workspaceContainer.'/docs';
        $docsCount = is_dir($docsDir) ? count($this->listFiles($docsDir)) : 0;

        return new BriefImportResult(
            workspaceHostPath: $workspaceHost,
            workspaceContainerPath: $workspaceContainer,
            claudeMdRelativePath: $claudeMdRelative,
            extractedFileCount: $extractedCount,
            docsFileCount: $docsCount,
            totalBytes: $this->dirSize($workspaceContainer),
            parsedClaudeMd: $parsed,
        );
    }

    private function assertSafeSlug(string $slug): void
    {
        // Lowercase ASCII alphanumeric + dashes; no dots, no slashes, no spaces.
        // Identical to App\Domain\Shared\ValueObjects\Slug rules.
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new RuntimeException(
                "Refusing to import: '{$slug}' is not a safe slug ".
                '(lowercase alphanumeric and dashes only).',
            );
        }
    }

    /**
     * If the ZIP wraps everything in `top-folder/CLAUDE.md`, move children up.
     */
    private function flattenSingleTopLevelFolder(string $workspaceContainer): void
    {
        $entries = array_values(array_diff(scandir($workspaceContainer) ?: [], ['.', '..']));
        if (count($entries) !== 1) {
            return;
        }

        $sole = $workspaceContainer.'/'.$entries[0];
        if (! is_dir($sole)) {
            return;
        }

        // Don't flatten if the inner folder doesn't contain CLAUDE.md or docs/
        $hasClaudeMd = is_file($sole.'/CLAUDE.md');
        $hasDocs = is_dir($sole.'/docs');
        if (! $hasClaudeMd && ! $hasDocs) {
            return;
        }

        foreach (array_diff(scandir($sole) ?: [], ['.', '..']) as $child) {
            rename($sole.'/'.$child, $workspaceContainer.'/'.$child);
        }
        rmdir($sole);
    }

    private function findClaudeMd(string $workspaceContainer): ?string
    {
        // Conventionally at the root, but accept a one-level-down location.
        if (is_file($workspaceContainer.'/CLAUDE.md')) {
            return 'CLAUDE.md';
        }
        foreach (array_diff(scandir($workspaceContainer) ?: [], ['.', '..']) as $entry) {
            $sub = $workspaceContainer.'/'.$entry;
            if (is_dir($sub) && is_file($sub.'/CLAUDE.md')) {
                return $entry.'/CLAUDE.md';
            }
        }

        return null;
    }

    /**
     * Light parse of CLAUDE.md — extracts well-known facts without depending
     * on a strict schema (briefs are hand-written by the developer in French
     * or English, structure varies).
     *
     * @return array<string, mixed>
     */
    private function parseClaudeMd(string $absolutePath): array
    {
        $content = (string) file_get_contents($absolutePath);
        $bytes = strlen($content);

        // First non-empty H1 heading is the project title.
        $title = null;
        if (preg_match('/^#\s+(.+?)\s*$/m', $content, $m) === 1) {
            $title = trim($m[1]);
        }

        // Crude stack detection — looks for tech-name keywords in the first 8 KB.
        $head = mb_substr($content, 0, 8192);
        $stackHits = [];
        $stackPatterns = [
            'Laravel' => '/\bLaravel\b\s*\|?\s*(\d{1,2})?/i',
            'Filament' => '/\bFilament\b\s*\|?\s*(\d{1,2})?/i',
            'Next.js' => '/\bNext\.?js\b\s*\|?\s*(\d{1,2})?/i',
            'Nuxt' => '/\bNuxt\b/i',
            'Vue' => '/\bVue(\.js)?\b/i',
            'React' => '/\bReact\b/i',
            'TypeScript' => '/\bTypeScript\b/i',
            'PostgreSQL' => '/\b(PostgreSQL|Postgres)\b/i',
            'MySQL' => '/\bMySQL\b/i',
            'Redis' => '/\bRedis\b/i',
            'Docker' => '/\bDocker\b/i',
            'Tailwind' => '/\bTailwind\b/i',
            'Stripe' => '/\bStripe\b/i',
        ];
        foreach ($stackPatterns as $name => $pattern) {
            if (preg_match($pattern, $head) === 1) {
                $stackHits[] = $name;
            }
        }

        // Production URL — first https://… line.
        $productionUrl = null;
        if (preg_match('#https?://[^\s<>"\']+#', $head, $u) === 1) {
            $productionUrl = $u[0];
        }

        // Number of distinct H2 sections (loose proxy for "spec depth").
        $h2Count = preg_match_all('/^##\s+/m', $content);

        return [
            'title' => $title,
            'bytes' => $bytes,
            'h2_section_count' => is_int($h2Count) ? $h2Count : 0,
            'detected_stack' => $stackHits,
            'detected_production_url' => $productionUrl,
        ];
    }

    /**
     * @return list<string>
     */
    private function listFiles(string $dir): array
    {
        $files = [];
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = $dir.'/'.$entry;
            if (is_file($path)) {
                $files[] = $entry;
            }
        }

        return $files;
    }

    private function dirSize(string $dir): int
    {
        $total = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iter as $fileInfo) {
            if ($fileInfo instanceof SplFileInfo && $fileInfo->isFile()) {
                $total += $fileInfo->getSize();
            }
        }

        return $total;
    }
}
