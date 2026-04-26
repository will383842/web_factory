<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Listeners;

use App\Domain\Catalog\Events\ContentProduced;
use App\Infrastructure\Pipeline\Jobs\DeployProjectJob;

/**
 * Sprint 16 — auto-chains pipeline step 7 (deploy) right after step 6
 * (ContentProduced). This closes the 7-step pipeline loop:
 *
 *   ProjectCreated → analyze → blueprint → design → build → github →
 *     content → DEPLOY → ProjectDeployed
 */
final class StartDeployOnContentProduced
{
    public function handle(ContentProduced $event): void
    {
        DeployProjectJob::dispatch($event->aggregateId());
    }
}
