<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Listeners;

use App\Domain\Catalog\Events\ProjectCreated;
use App\Infrastructure\Pipeline\Jobs\AnalyzeProjectIdeaJob;
use App\Providers\DomainServiceProvider;

/**
 * Auto-kicks the 7-step pipeline on project submission.
 *
 * Wired manually in {@see DomainServiceProvider}.
 */
final class StartPipelineOnProjectCreated
{
    public function handle(ProjectCreated $event): void
    {
        AnalyzeProjectIdeaJob::dispatch($event->projectId);
    }
}
