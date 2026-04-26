<?php

declare(strict_types=1);

namespace App\Application\Operations\Services;

use App\Application\Operations\DTOs\BackupResult;

/**
 * Port for backup adapters.
 *
 * Sprint-12 default impl is a filesystem-only adapter that tars the project
 * brief artifacts (under MinIO disk `s3` projects/{id}/) and writes the
 * archive on the local Storage. Sprint 16 (Deployment) will swap in:
 *  - BorgBackupService for VPS-level backups
 *  - R2BackupService (Cloudflare) for off-site #1
 *  - B2BackupService (Backblaze) for off-site #2
 *
 * The 5-level backup strategy stays consistent: each target implements the
 * same port, the BackupRunner orchestrates them in cascade.
 */
interface BackupService
{
    /**
     * @param array<string, mixed> $options
     */
    public function run(string $kind, ?int $projectId, array $options = []): BackupResult;

    public function targetName(): string;

    public function restore(string $archivePath): bool;
}
