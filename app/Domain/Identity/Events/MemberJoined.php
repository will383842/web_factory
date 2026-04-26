<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

/**
 * Sprint 13.2 — emitted when a user accepts a team invitation. Used by
 * notifications (welcome the new member) and analytics (seat utilization).
 */
final class MemberJoined extends DomainEvent
{
    public function __construct(
        public readonly int $teamId,
        public readonly int $userId,
        public readonly string $role,
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
        return 'identity.team.member_joined';
    }
}
