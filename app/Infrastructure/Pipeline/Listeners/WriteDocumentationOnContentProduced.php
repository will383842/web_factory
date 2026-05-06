<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Listeners;

use App\Domain\Catalog\Events\ContentProduced;
use App\Infrastructure\Pipeline\Jobs\WriteProjectDocumentationJob;

/**
 * Sprint 26 — chains per-platform CLAUDE.md generation right after step 6
 * (ContentProduced). Runs in parallel with {@see StartDeployOnContentProduced}:
 * the deploy job ships the runtime, this listener pushes the documentation
 * — independent side-effects, no ordering requirement.
 */
final class WriteDocumentationOnContentProduced
{
    public function handle(ContentProduced $event): void
    {
        WriteProjectDocumentationJob::dispatch($event->aggregateId());
    }
}
