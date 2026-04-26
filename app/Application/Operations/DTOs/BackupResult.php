<?php

declare(strict_types=1);

namespace App\Application\Operations\DTOs;

use App\Application\Operations\Services\BackupService;

/**
 * Returned by every {@see BackupService}
 * `run()` call. The caller is responsible for persisting it on a `backups`
 * row (the BackupRunner does that automatically).
 */
final readonly class BackupResult
{
    /**
     * @param array<string, mixed> $manifest
     */
    public function __construct(
        public bool $success,
        public string $archivePath,
        public int $sizeBytes,
        public string $checksumSha256,
        public array $manifest,
        public ?string $errorMessage = null,
    ) {}
}
