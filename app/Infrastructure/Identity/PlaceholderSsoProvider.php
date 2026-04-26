<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\DTOs\SsoUserProfile;
use App\Application\Identity\Services\SsoProvider;
use Illuminate\Support\Str;

/**
 * Sprint-13.2 placeholder driver for an arbitrary SSO provider.
 *
 * Authorization URL points at a synthetic in-app callback handler so a Pest
 * test can drive the full flow without leaving the boundary. `exchangeCode`
 * decodes the synthetic code (`sso_test:{provider_user_id}:{email}`) and
 * returns the matching {@see SsoUserProfile}.
 *
 * Sprint 16 swaps this for one Socialite-backed instance per provider name.
 */
final class PlaceholderSsoProvider implements SsoProvider
{
    public function __construct(private readonly string $name) {}

    public function name(): string
    {
        return $this->name;
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        $params = http_build_query([
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'provider' => $this->name,
        ]);

        return "https://sso.test/{$this->name}/authorize?{$params}";
    }

    public function exchangeCode(string $code, string $redirectUri): SsoUserProfile
    {
        // Synthetic format: sso_test:<provider_user_id>:<email>
        if (Str::startsWith($code, 'sso_test:')) {
            [, $providerUserId, $email] = explode(':', $code, 3);
        } else {
            // Fallback: deterministic synthetic identity from the code itself
            $providerUserId = 'pid_'.substr(hash('sha256', $code), 0, 16);
            $email = $providerUserId.'@example.com';
        }

        return new SsoUserProfile(
            provider: $this->name,
            providerUserId: $providerUserId,
            email: $email,
            name: null,
            accessToken: 'tok_'.Str::random(32),
            refreshToken: 'ref_'.Str::random(32),
            expiresIn: 3600,
            rawPayload: ['placeholder' => true, 'redirect_uri' => $redirectUri],
        );
    }
}
