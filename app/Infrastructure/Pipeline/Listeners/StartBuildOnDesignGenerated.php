<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Listeners;

use App\Domain\Catalog\Events\DesignGenerated;
use App\Infrastructure\Pipeline\Jobs\BuildBriefJob;

/**
 * Auto-chains pipeline steps 4-5 right after step 3 (DesignGenerated).
 */
final class StartBuildOnDesignGenerated
{
    public function handle(DesignGenerated $event): void
    {
        BuildBriefJob::dispatch($event->aggregateId());
    }
}
