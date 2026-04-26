<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Application\Identity\DTOs\SsoUserProfile;
use App\Models\SsoIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sprint 13.2 — turns an {@see SsoUserProfile} into either:
 *  1) a fresh User + linked SsoIdentity (first-time SSO sign-up), OR
 *  2) the matching existing User (returning user, identified by provider+pid),
 *     OR
 *  3) the User whose verified email matches the SSO profile (auto-link of an
 *     already-registered account).
 */
final class SsoIdentityLinker
{
    public function findOrCreateUser(SsoUserProfile $profile): User
    {
        return DB::transaction(function () use ($profile): User {
            // 1. Existing SSO identity → return its user
            $identity = SsoIdentity::query()
                ->where('provider', $profile->provider)
                ->where('provider_user_id', $profile->providerUserId)
                ->first();

            if ($identity !== null) {
                $this->touchTokens($identity, $profile);

                /** @var User $linked */
                $linked = User::query()->findOrFail($identity->user_id);

                return $linked;
            }

            // 2. User exists by email → auto-link
            $user = User::query()->where('email', $profile->email)->first();

            // 3. Create a fresh user
            if ($user === null) {
                $user = new User;
                $user->forceFill([
                    'name' => $profile->name ?? $profile->email,
                    'email' => $profile->email,
                    'password' => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                ])->save();
            }

            SsoIdentity::query()->create([
                'user_id' => $user->getKey(),
                'provider' => $profile->provider,
                'provider_user_id' => $profile->providerUserId,
                'email' => $profile->email,
                'access_token_encrypted' => $profile->accessToken,
                'refresh_token_encrypted' => $profile->refreshToken,
                'expires_at' => $profile->expiresIn !== null ? now()->addSeconds($profile->expiresIn) : null,
                'raw_payload' => $profile->rawPayload,
            ]);

            return $user;
        });
    }

    private function touchTokens(SsoIdentity $identity, SsoUserProfile $profile): void
    {
        $identity->forceFill([
            'access_token_encrypted' => $profile->accessToken ?? $identity->access_token_encrypted,
            'refresh_token_encrypted' => $profile->refreshToken ?? $identity->refresh_token_encrypted,
            'expires_at' => $profile->expiresIn !== null ? now()->addSeconds($profile->expiresIn) : $identity->expires_at,
            'raw_payload' => $profile->rawPayload,
        ])->save();
    }
}
