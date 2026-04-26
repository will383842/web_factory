<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Events;

use App\Domain\Shared\Events\DomainEvent;

final class GitHubRepositoryCreated extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $fullName,
        public readonly string $htmlUrl,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->projectId;
    }

    public function eventName(): string
    {
        return 'catalog.project.github_repo_created';
    }
}
