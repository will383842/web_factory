<?php

declare(strict_types=1);

namespace App\Application\Identity\Commands;

/**
 * Command DTO for registering a new user.
 *
 * Carries plain primitive input so handlers can validate and convert into
 * Domain Value Objects (Email, etc.) themselves.
 */
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $name,
        public string $plainPassword,
    ) {}
}
