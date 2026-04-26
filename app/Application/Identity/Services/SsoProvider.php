<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Application\Identity\DTOs\SsoUserProfile;

/**
 * Port for SSO providers (Google, Microsoft, Apple, Okta, GitHub, ...).
 *
 * The Sprint-13.2 default impl is a placeholder driver that does NOT call any
 * real OAuth/OIDC endpoint — it accepts any (`provider`, `provider_user_id`,
 * `email`) tuple and produces a deterministic profile so the rest of the
 * stack (controllers, Filament admin, tests) can be wired without real
 * credentials. Sprint 16 swaps in a Socialite-backed adapter (laravel/socialite
 * with Google / Microsoft / Apple / Okta keys).
 *
 * The port stays unchanged in Sprint 16 — only the bound implementation flips.
 */
interface SsoProvider
{
    public function name(): string;

    /**
     * Build the provider authorization URL for the given `state` token.
     */
    public function authorizationUrl(string $state, string $redirectUri): string;

    /**
     * Exchange the OAuth `code` for a normalized {@see SsoUserProfile}.
     */
    public function exchangeCode(string $code, string $redirectUri): SsoUserProfile;
}
