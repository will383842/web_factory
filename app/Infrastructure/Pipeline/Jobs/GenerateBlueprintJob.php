<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\BlueprintGenerationService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\BlueprintGenerated;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Pipeline Step 2 — blueprint (pages, journeys, KPIs).
 */
final class GenerateBlueprintJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        BlueprintGenerationService $generator,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $project->transitionTo(ProjectStatus::Blueprinting);

        $blueprint = $generator->generate($project);
        $project->metadata = array_merge($project->metadata, ['blueprint' => $blueprint->toMetadataArray()]);

        $projects->save($project);

        $events->dispatchAll($project->flushEvents());
        $events->dispatch(new BlueprintGenerated(
            $project->id,
            count($blueprint->pages),
            count($blueprint->journeys),
        ));

        GenerateDesignJob::dispatch($project->id);
    }
}
