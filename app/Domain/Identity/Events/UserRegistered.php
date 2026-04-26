<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Shared\Events\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly Email $email,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->userId;
    }

    public function eventName(): string
    {
        return 'identity.user.registered';
    }
}
