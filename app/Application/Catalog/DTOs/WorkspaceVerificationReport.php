<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

use App\Application\Catalog\Services\WorkspaceVerifier;

/**
 * Sprint 28 — output of {@see WorkspaceVerifier::verify()}.
 *
 * `passed` and `failed` are dictionaries `check_id => human_message`.
 * `score` is a 0-100 percentage of passed checks; >= 80 is the "ready"
 * threshold surfaced by the Filament UI.
 */
final readonly class WorkspaceVerificationReport
{
    /**
     * @param array<string, string> $passed
     * @param array<string, string> $failed
     * @param array<string, string> $warnings
     */
    public function __construct(
        public string $workspacePath,
        public bool $workspaceExists,
        public int $score,
        public array $passed,
        public array $failed,
        public array $warnings,
    ) {}

    public function isReady(): bool
    {
        return $this->workspaceExists && $this->score >= 80 && $this->failed === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'workspace_path' => $this->workspacePath,
            'workspace_exists' => $this->workspaceExists,
            'score' => $this->score,
            'passed_count' => count($this->passed),
            'failed_count' => count($this->failed),
            'warnings_count' => count($this->warnings),
            'passed' => $this->passed,
            'failed' => $this->failed,
            'warnings' => $this->warnings,
            'verified_at' => now()->toIso8601String(),
        ];
    }
}
