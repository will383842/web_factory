<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row for every backup run (full / incremental / snapshot) against
 * any of our targets (local / r2 / b2 / gdrive / borg).
 *
 * `manifest` carries adapter-specific metadata (R2 bucket key, Borg archive
 * id, etc.). The Filament admin lists these and can request restores.
 */
final class Backup extends Model
{
    public const KIND_FULL = 'full';

    public const KIND_INCREMENTAL = 'incremental';

    public const KIND_SNAPSHOT = 'snapshot';

    public const TARGET_LOCAL = 'local';

    public const TARGET_R2 = 'r2';

    public const TARGET_B2 = 'b2';

    public const TARGET_GDRIVE = 'gdrive';

    public const TARGET_BORG = 'borg';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_id', 'kind', 'target', 'status', 'archive_path',
        'size_bytes', 'checksum_sha256', 'manifest', 'error_message',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'manifest' => AsArrayObject::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at === null || $this->finished_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }
}
