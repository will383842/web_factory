<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\IdeaAnalysisService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\IdeaAnalyzed;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Pipeline Step 1 — analyse idée + scoring.
 *
 * Loads the Project, transitions it Draft → Analyzing, runs the
 * {@see IdeaAnalysisService}, persists scores + clarifications in metadata,
 * dispatches IdeaAnalyzed, then chains GenerateBlueprintJob.
 */
final class AnalyzeProjectIdeaJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        IdeaAnalysisService $analyzer,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $project->transitionTo(ProjectStatus::Analyzing);

        $result = $analyzer->analyze($project);
        $project->score($result->viralityScore, $result->valueScore);
        $project->metadata = array_merge($project->metadata, ['analysis' => $result->toMetadataArray()]);

        $projects->save($project);

        $events->dispatchAll($project->flushEvents());
        $events->dispatch(new IdeaAnalyzed($project->id, $result->viralityScore, $result->valueScore));

        // Chain step 2
        GenerateBlueprintJob::dispatch($project->id);
    }
}
