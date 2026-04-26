<?php

declare(strict_types=1);

use App\Application\Operations\DTOs\BackupResult;
use App\Application\Operations\Services\BackupRunner;
use App\Application\Operations\Services\BackupService;
use App\Infrastructure\Operations\LocalFilesystemBackupService;
use App\Models\Backup;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
    Storage::fake('s3');
});

it('binds BackupService to the local-filesystem placeholder', function (): void {
    expect(app(BackupService::class))->toBeInstanceOf(LocalFilesystemBackupService::class);
});

it('LocalFilesystemBackupService writes a manifest to local storage', function (): void {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'p1', 'name' => 'P1', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);

    Storage::disk('s3')->put("projects/{$project->id}/brief.md", '# brief');

    $result = app(BackupService::class)->run(Backup::KIND_FULL, (int) $project->id);

    expect($result->success)->toBeTrue()
        ->and($result->archivePath)->toStartWith('backups/full/')
        ->and($result->checksumSha256)->toMatch('/^[a-f0-9]{64}$/')
        ->and($result->manifest['s3_file_count'])->toBe(1);

    Storage::disk('local')->assertExists($result->archivePath);
});

it('BackupRunner persists a succeeded Backup row when adapter succeeds', function (): void {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'p2', 'name' => 'P2', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);

    $row = app(BackupRunner::class)->run(Backup::KIND_SNAPSHOT, (int) $project->id);

    expect($row->status)->toBe(Backup::STATUS_SUCCEEDED)
        ->and($row->target)->toBe(Backup::TARGET_LOCAL)
        ->and($row->kind)->toBe(Backup::KIND_SNAPSHOT)
        ->and($row->project_id)->toBe($project->id)
        ->and($row->archive_path)->toStartWith('backups/snapshot/')
        ->and($row->size_bytes)->toBeGreaterThan(0)
        ->and($row->started_at)->not->toBeNull()
        ->and($row->finished_at)->not->toBeNull();
});

it('BackupRunner accepts platform-wide backups (project_id null)', function (): void {
    $row = app(BackupRunner::class)->run(Backup::KIND_FULL, null);

    expect($row->status)->toBe(Backup::STATUS_SUCCEEDED)
        ->and($row->project_id)->toBeNull();
});

it('BackupRunner records a failed row when adapter throws', function (): void {
    app()->bind(BackupService::class, function (): BackupService {
        return new class implements BackupService
        {
            public function run(string $kind, ?int $projectId, array $options = []): BackupResult
            {
                throw new RuntimeException('boom');
            }

            public function targetName(): string
            {
                return Backup::TARGET_LOCAL;
            }

            public function restore(string $archivePath): bool
            {
                return false;
            }
        };
    });

    $row = app(BackupRunner::class)->run(Backup::KIND_FULL, null);

    expect($row->status)->toBe(Backup::STATUS_FAILED)
        ->and($row->error_message)->toBe('boom');
});

it('admin reaches /admin/backups index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/backups')->assertOk();
});
