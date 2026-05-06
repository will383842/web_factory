<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Domain\Catalog\Entities\Project;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Renders a per-platform `CLAUDE.md` so any Claude Code session opened on
 * the generated site repository starts with full project context (stack,
 * locales, content footprint, deployment URL, GitHub coordinates).
 *
 * Inputs come from the project metadata accumulated by the 7-step pipeline:
 *   - metadata.brief         (Sprint 6 — file structure + checksum)
 *   - metadata.blueprint     (Sprint 5 — pages, journeys, KPIs)
 *   - metadata.design        (Sprint 5 — tokens, mockups)
 *   - metadata.github        (Sprint 6 — repo coords)
 *   - metadata.content       (Sprint 15 — produced rows + locales)
 *   - metadata.deployment    (Sprint 16 — provider + live URL)
 *
 * Pure rendering — no I/O, no event dispatch. The job that calls this is
 * responsible for committing the result via `GitHubRepositoryService`.
 */
final class ProjectDocumentationGenerator
{
    private const TEMPLATE = 'generators.project-claude-md';

    public function __construct(private readonly ViewFactory $views) {}

    public function generateClaudeMd(Project $project): string
    {
        return rtrim($this->views->make(self::TEMPLATE, [
            'project' => $project,
            'metadata' => $project->metadata,
            'generatedAt' => now()->toIso8601String(),
        ])->render(), "\n")."\n";
    }
}
