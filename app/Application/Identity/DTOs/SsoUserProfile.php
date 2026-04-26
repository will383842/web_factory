<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use App\Application\Identity\Services\SsoProvider;

/**
 * Returned by an {@see SsoProvider} after a
 * redirect callback. Provider-agnostic shape — the adapter normalizes Google
 * `sub`, Microsoft `oid`, Apple `sub`, Okta `sub` into `providerUserId`.
 */
final readonly class SsoUserProfile
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public string $email,
        public ?string $name = null,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?int $expiresIn = null,
        public array $rawPayload = [],
    ) {}
}
