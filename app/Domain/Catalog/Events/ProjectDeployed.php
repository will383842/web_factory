<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Sprint 16 — emitted when pipeline step 7 successfully ships the project to
 * production. The Sprint 8 IndexNow ping listens to this; a Sprint 18
 * observability listener can also use it to mark the build as "deployed" in
 * Grafana annotations.
 */
final class ProjectDeployed extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $liveUrl,
        public readonly string $provider,
        public readonly ?string $deploymentId = null,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.deployed';
    }
}
