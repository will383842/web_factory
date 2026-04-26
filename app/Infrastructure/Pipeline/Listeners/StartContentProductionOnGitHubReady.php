<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Listeners;

use App\Domain\Catalog\Events\GitHubRepositoryCreated;
use App\Infrastructure\Pipeline\Jobs\ProduceContentJob;

/**
 * Sprint 15 — auto-chains step 6 (Content production) right after step 5
 * (GitHubRepositoryCreated). Sprint 16 will add a step 7 listener
 * (StartDeployOnContentProduced) wired to ContentProduced.
 */
final class StartContentProductionOnGitHubReady
{
    public function handle(GitHubRepositoryCreated $event): void
    {
        ProduceContentJob::dispatch($event->aggregateId());
    }
}
