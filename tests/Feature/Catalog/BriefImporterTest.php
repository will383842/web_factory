<?php

declare(strict_types=1);

use App\Application\Catalog\Services\BriefImporter;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function makeBriefZip(string $zipPath, callable $populator): void
{
    $tempDir = sys_get_temp_dir().'/wf-brief-fix-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tempDir);
    $populator($tempDir);

    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot open zip for writing');
    }

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );
    foreach ($iter as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }
        $relative = ltrim(str_replace($tempDir, '', $file->getPathname()), '/\\');
        $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
    }
    $zip->close();
    File::deleteDirectory($tempDir);
}

/**
 * @return array{0: BriefImporter, 1: string}
 */
function makeImporterWithTempBase(): array
{
    $base = sys_get_temp_dir().'/wf-projects-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($base);
    $importer = new BriefImporter(hostBasePath: $base, containerBasePath: $base);

    return [$importer, $base];
}

it('extracts a flat brief ZIP and parses CLAUDE.md', function (): void {
    [$importer, $base] = makeImporterWithTempBase();

    $zip = sys_get_temp_dir().'/wf-brief-flat-'.bin2hex(random_bytes(4)).'.zip';
    makeBriefZip($zip, function (string $tmp): void {
        File::put($tmp.'/CLAUDE.md', "# AxionIA\n\n## Stack\n\nLaravel 11 + Filament 3 + PostgreSQL\n\n## URL\n\nhttps://axionia.fr\n");
        File::ensureDirectoryExists($tmp.'/docs');
        File::put($tmp.'/docs/01-Vision.md', '# Vision');
        File::put($tmp.'/docs/02-Tech.md', '# Tech');
    });

    $result = $importer->import($zip, 'axionia');

    expect($result->workspaceContainerPath)->toBe($base.'/axionia')
        ->and($result->workspaceHostPath)->toBe($base.'/axionia')
        ->and($result->claudeMdRelativePath)->toBe('CLAUDE.md')
        ->and($result->docsFileCount)->toBe(2)
        ->and($result->parsedClaudeMd['title'])->toBe('AxionIA')
        ->and($result->parsedClaudeMd['detected_stack'])->toContain('Laravel')
        ->and($result->parsedClaudeMd['detected_stack'])->toContain('Filament')
        ->and($result->parsedClaudeMd['detected_stack'])->toContain('PostgreSQL')
        ->and($result->parsedClaudeMd['detected_production_url'])->toBe('https://axionia.fr')
        ->and($result->parsedClaudeMd['h2_section_count'])->toBe(2)
        ->and($result->totalBytes)->toBeGreaterThan(0);

    expect(file_exists($base.'/axionia/CLAUDE.md'))->toBeTrue()
        ->and(file_exists($base.'/axionia/docs/01-Vision.md'))->toBeTrue();

    File::deleteDirectory($base);
    File::delete($zip);
});

it('flattens a single-folder ZIP wrapper so CLAUDE.md sits at workspace root', function (): void {
    [$importer, $base] = makeImporterWithTempBase();

    $zip = sys_get_temp_dir().'/wf-brief-wrap-'.bin2hex(random_bytes(4)).'.zip';
    makeBriefZip($zip, function (string $tmp): void {
        File::ensureDirectoryExists($tmp.'/wrapper-folder/docs');
        File::put($tmp.'/wrapper-folder/CLAUDE.md', "# Wrapped\n\n## A\n\n## B\n");
        File::put($tmp.'/wrapper-folder/docs/spec.md', '# spec');
    });

    $result = $importer->import($zip, 'wrap-test');

    expect($result->claudeMdRelativePath)->toBe('CLAUDE.md')
        ->and($result->docsFileCount)->toBe(1);

    expect(file_exists($base.'/wrap-test/CLAUDE.md'))->toBeTrue()
        ->and(is_dir($base.'/wrap-test/wrapper-folder'))->toBeFalse();

    File::deleteDirectory($base);
    File::delete($zip);
});

it('rejects an unsafe slug', function (): void {
    [$importer, $base] = makeImporterWithTempBase();

    $zip = sys_get_temp_dir().'/wf-brief-slug-'.bin2hex(random_bytes(4)).'.zip';
    makeBriefZip($zip, function (string $tmp): void {
        File::put($tmp.'/CLAUDE.md', '# x');
    });

    $importer->import($zip, '../escape');
})->throws(RuntimeException::class, 'not a safe slug');

it('refuses to overwrite an existing workspace', function (): void {
    [$importer, $base] = makeImporterWithTempBase();
    File::ensureDirectoryExists($base.'/already-here');

    $zip = sys_get_temp_dir().'/wf-brief-clash-'.bin2hex(random_bytes(4)).'.zip';
    makeBriefZip($zip, function (string $tmp): void {
        File::put($tmp.'/CLAUDE.md', '# x');
    });

    $importer->import($zip, 'already-here');
})->throws(RuntimeException::class, 'already exists');

it('handles a brief without a CLAUDE.md gracefully (returns null relative path)', function (): void {
    [$importer, $base] = makeImporterWithTempBase();

    $zip = sys_get_temp_dir().'/wf-brief-no-claude-'.bin2hex(random_bytes(4)).'.zip';
    makeBriefZip($zip, function (string $tmp): void {
        File::put($tmp.'/README.md', '# orphan');
    });

    $result = $importer->import($zip, 'no-claude');

    expect($result->claudeMdRelativePath)->toBeNull()
        ->and($result->parsedClaudeMd)->toBe([]);

    File::deleteDirectory($base);
    File::delete($zip);
});

it('binds BriefImporter as a singleton via DomainServiceProvider', function (): void {
    $a = app(BriefImporter::class);
    $b = app(BriefImporter::class);
    expect($a)->toBeInstanceOf(BriefImporter::class)
        ->and($a)->toBe($b);
});

it('exposes the upload-brief Filament page', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/upload-brief')
        ->assertOk();
});
