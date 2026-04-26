<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\DTOs\BackupResult;
use App\Application\Operations\Services\BackupService;
use App\Models\Backup as BackupModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sprint-12 placeholder backup adapter. Writes a JSON manifest of the
 * project's S3 brief contents to the local Storage. Real adapters (Borg,
 * R2, B2) land Sprint 16 — they implement the same port unchanged.
 *
 * Storage layout: `local::backups/{kind}/{timestamp}-{ulid}.json`
 */
final class LocalFilesystemBackupService implements BackupService
{
    public function targetName(): string
    {
        return BackupModel::TARGET_LOCAL;
    }

    public function run(string $kind, ?int $projectId, array $options = []): BackupResult
    {
        $stamp = now()->format('Ymd-His');
        $id = Str::ulid();
        $path = "backups/{$kind}/{$stamp}-{$id}.json";

        $manifest = [
            'kind' => $kind,
            'project_id' => $projectId,
            'created_at' => now()->toAtomString(),
            'options' => $options,
        ];

        if ($projectId !== null) {
            // Capture an index of the project's brief files (Sprint 6 produces those).
            $files = Storage::disk('s3')->files("projects/{$projectId}");
            $manifest['s3_files'] = $files;
            $manifest['s3_file_count'] = count($files);
        }

        $payload = (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        Storage::disk('local')->put($path, $payload);

        return new BackupResult(
            success: true,
            archivePath: $path,
            sizeBytes: mb_strlen($payload),
            checksumSha256: hash('sha256', $payload),
            manifest: $manifest,
        );
    }

    public function restore(string $archivePath): bool
    {
        return Storage::disk('local')->exists($archivePath);
    }
}
