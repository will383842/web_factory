<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\GitHubRepositoryCreated;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Pipeline Step 5 — create the GitHub repository for the project and
 * stash its coordinates on the Project metadata.
 */
final class InitGitHubRepoJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        GitHubRepositoryService $github,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $repo = $github->createRepository($project);

        $project->metadata = array_merge($project->metadata, ['github' => $repo->toMetadataArray()]);
        $projects->save($project);

        $events->dispatch(new GitHubRepositoryCreated($project->id, $repo->fullName, $repo->htmlUrl));
    }
}
