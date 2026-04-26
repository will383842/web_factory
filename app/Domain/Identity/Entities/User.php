<?php

declare(strict_types=1);

namespace App\Domain\Identity\Entities;

use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Identity\Events\UserRegistered;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Shared\Entities\AggregateRoot;

/**
 * Identity aggregate root representing an authenticated user.
 *
 * Persistence is delegated to {@see UserRepositoryInterface}.
 * No Eloquent / no Illuminate dependency: the Eloquent model lives in
 * App\Infrastructure\Persistence\Eloquent and translates to/from this entity.
 */
final class User extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly Email $email,
        public readonly string $name,
    ) {}

    public static function register(string $id, Email $email, string $name): self
    {
        $user = new self($id, $email, $name);
        $user->recordEvent(new UserRegistered($id, $email));

        return $user;
    }

    public static function rehydrate(string $id, Email $email, string $name): self
    {
        return new self($id, $email, $name);
    }
}
