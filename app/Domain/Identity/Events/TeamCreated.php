<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.2 — emitted when a Team is created. The Sprint-13.3 onboarding
 * orchestrator listens to this to start the team-onboarding flow.
 */
final class TeamCreated extends DomainEvent
{
    public function __construct(
        public readonly int $teamId,
        public readonly int $ownerId,
        public readonly string $slug,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($occurredAt);
    }

    public function aggregateId(): string
    {
        return (string) $this->teamId;
    }

    public function eventName(): string
    {
        return 'identity.team.created';
    }
}
