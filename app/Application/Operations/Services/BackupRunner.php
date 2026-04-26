<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Models\Backup;
use Throwable;

/**
 * Orchestrates a backup run: creates the audit row, calls the adapter,
 * fills in result/error, returns the persisted Backup model.
 *
 * Sprint 12 wires a single adapter (the local-filesystem placeholder).
 * Sprint 16 will inject a list of adapters (R2 + B2 + Borg) and run them
 * in cascade so a single Filament action triggers the full 5-level backup.
 */
final class BackupRunner
{
    public function __construct(private readonly BackupService $backup) {}

    /**
     * @param array<string, mixed> $options
     */
    public function run(string $kind, ?int $projectId, array $options = []): Backup
    {
        /** @var Backup $row */
        $row = Backup::query()->create([
            'project_id' => $projectId,
            'kind' => $kind,
            'target' => $this->backup->targetName(),
            'status' => Backup::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $result = $this->backup->run($kind, $projectId, $options);

            $row->fill([
                'status' => $result->success ? Backup::STATUS_SUCCEEDED : Backup::STATUS_FAILED,
                'archive_path' => $result->archivePath,
                'size_bytes' => $result->sizeBytes,
                'checksum_sha256' => $result->checksumSha256,
                'manifest' => $result->manifest,
                'error_message' => $result->errorMessage,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $row->fill([
                'status' => Backup::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ])->save();
        }

        return $row->fresh() ?? $row;
    }
}
