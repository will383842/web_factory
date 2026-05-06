<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

use App\Application\Catalog\Services\WorkspaceGitPusher;

/**
 * Sprint 29 — Outcome of {@see WorkspaceGitPusher::push()}.
 *
 * `phases` records the success/failure of each git step in order so the UI
 * can render a precise status (which step failed and why).
 *
 * `manualCommand` is only populated when the push step itself failed, so
 * the developer can copy-paste it into their host terminal to retry.
 */
final readonly class GitPushResult
{
    /**
     * @param array<string, bool> $phases
     */
    public function __construct(
        public bool $success,
        public string $remoteUrl,
        public ?string $commitSha,
        public ?string $manualCommand,
        public string $output,
        public ?string $errorMessage,
        public array $phases,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'success' => $this->success,
            'remote_url' => $this->remoteUrl,
            'commit_sha' => $this->commitSha,
            'manual_command' => $this->manualCommand,
            'phases' => $this->phases,
            'error_message' => $this->errorMessage,
            'pushed_at' => now()->toIso8601String(),
        ];
    }
}
