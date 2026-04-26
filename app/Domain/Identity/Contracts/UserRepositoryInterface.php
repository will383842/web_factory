<?php

declare(strict_types=1);

namespace App\Domain\Identity\Contracts;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;

/**
 * Persistence port for the Identity\User aggregate.
 *
 * Implementations live in App\Infrastructure\Persistence\Eloquent.
 */
interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;

    public function delete(string $id): void;
}
