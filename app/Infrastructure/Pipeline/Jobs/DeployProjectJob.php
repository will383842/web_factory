<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\DeploymentService;
use App\Application\Marketing\Services\IndexNowPingService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\ProjectDeployed;
use App\Domain\Catalog\Exceptions\InvalidProjectStatusTransitionException;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Sprint 16 — Pipeline Step 7: ship the project to production.
 *
 * Side-effects on success:
 *   - Persist `metadata.deployment` with the live URL
 *   - Transition project status to `deployed`
 *   - Dispatch ProjectDeployed (used by IndexNow + observability)
 *   - Ping IndexNow with the new live URL
 */
final class DeployProjectJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        DeploymentService $deployer,
        DomainEventDispatcher $events,
        IndexNowPingService $indexNow,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $result = $deployer->deploy($project);

        $project->metadata = array_merge($project->metadata, [
            'deployment' => $result->toMetadataArray(),
        ]);

        if ($result->success && $result->liveUrl !== null) {
            // Status transition through the aggregate guard (Building → Deployed)
            if ($project->status !== ProjectStatus::Deployed) {
                try {
                    $project->transitionTo(ProjectStatus::Deployed);
                } catch (InvalidProjectStatusTransitionException) {
                    // Mid-pipeline restart — leave status as-is.
                }
            }

            $projects->save($project);

            $events->dispatch(new ProjectDeployed(
                projectId: $project->id,
                liveUrl: $result->liveUrl,
                provider: $result->provider,
                deploymentId: $result->deploymentId,
            ));

            $host = parse_url($result->liveUrl, PHP_URL_HOST) ?: 'localhost';
            $indexNow->ping($host, 'webfactory-deploy', [$result->liveUrl]);

            return;
        }

        $projects->save($project);
    }
}
