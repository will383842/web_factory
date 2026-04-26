<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entities;

use App\Domain\Compliance\Events\ConsentRecorded;
use App\Domain\Shared\Entities\AggregateRoot;
use DateTimeImmutable;

final class AuditLog extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $actorId,
        public readonly string $action,
        public readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function recordConsent(string $id, string $actorId, string $action): self
    {
        $log = new self($id, $actorId, $action, new DateTimeImmutable);
        $log->recordEvent(new ConsentRecorded($id, $actorId, $action));

        return $log;
    }

    public static function rehydrate(string $id, string $actorId, string $action, DateTimeImmutable $occurredAt): self
    {
        return new self($id, $actorId, $action, $occurredAt);
    }
}
