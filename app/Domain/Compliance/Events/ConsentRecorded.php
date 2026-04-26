<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Events;

use App\Domain\Shared\Events\DomainEvent;

final class ConsentRecorded extends DomainEvent
{
    public function __construct(
        public readonly string $auditLogId,
        public readonly string $actorId,
        public readonly string $action,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->auditLogId;
    }

    public function eventName(): string
    {
        return 'compliance.consent.recorded';
    }
}
