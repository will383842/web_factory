<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\DTOs\Blueprint;
use App\Application\Catalog\Services\DesignGenerationService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Pipeline Step 3 — design system + mockups.
 */
final class GenerateDesignJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        DesignGenerationService $designer,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $project->transitionTo(ProjectStatus::Designing);

        $blueprintMeta = $project->metadata['blueprint'] ?? [
            'pages' => [],
            'journeys' => [],
            'kpis' => [],
        ];
        /** @var array{pages: list<array{slug: string, title: string, type: string}>, journeys: list<array{name: string, steps: list<string>}>, kpis: list<array{key: string, target: string|float|int}>} $blueprintMeta */
        $blueprint = new Blueprint(
            pages: $blueprintMeta['pages'] ?? [],
            journeys: $blueprintMeta['journeys'] ?? [],
            kpis: $blueprintMeta['kpis'] ?? [],
        );

        $design = $designer->generate($project, $blueprint);
        $project->metadata = array_merge($project->metadata, ['design' => $design->toMetadataArray()]);

        $projects->save($project);

        $events->dispatchAll($project->flushEvents());
        $events->dispatch(new DesignGenerated($project->id, count($design->mockups)));
    }
}
