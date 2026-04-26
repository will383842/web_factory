<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\BriefBuilderService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\BriefBuilt;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\Storage;

/**
 * Pipeline Step 4a — assemble the 35-file brief, persist it on the S3 disk
 * (`projects/{id}/brief.json`), and stash a metadata snapshot on the Project.
 */
final class BuildBriefJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        BriefBuilderService $builder,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        // Steps 4-5 happen during the "Building" phase
        $project->transitionTo(ProjectStatus::Building);

        $bundle = $builder->build($project);

        // Stash the brief on the S3 disk; only metadata (file index + checksum)
        // is mirrored on the Project for the admin UI.
        Storage::disk('s3')->put(
            "projects/{$project->id}/brief.json",
            (string) json_encode($bundle->files, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        $project->metadata = array_merge($project->metadata, ['brief' => $bundle->toMetadataArray()]);
        $projects->save($project);

        $events->dispatchAll($project->flushEvents());
        $events->dispatch(new BriefBuilt($project->id, $bundle->fileCount(), $bundle->checksum));

        ScoreBriefJob::dispatch($project->id);
    }
}
