<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Identity\Services\SsoIdentityLinker;
use App\Application\Identity\Services\SsoProviderRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Sprint 13.2 — Generic OAuth/OIDC entry-point.
 *
 * `redirect()` returns the provider authorization URL + a CSRF state token.
 * `callback()` exchanges the code via the registered driver and returns a
 * Sanctum personal access token for the authenticated User.
 *
 * Sprint 16 swaps the placeholder driver for Socialite — same routes, same
 * shape, the only diff is real OAuth round-trips.
 */
final class SsoController extends Controller
{
    public function __construct(
        private readonly SsoProviderRegistry $registry,
        private readonly SsoIdentityLinker $linker,
    ) {}

    public function redirect(string $provider, Request $request): JsonResponse
    {
        $state = Str::random(40);
        $redirectUri = $request->input('redirect_uri', config('app.url').'/api/v1/auth/sso/'.$provider.'/callback');

        $url = $this->registry->get($provider)->authorizationUrl($state, (string) $redirectUri);

        return response()->json([
            'authorization_url' => $url,
            'state' => $state,
        ]);
    }

    public function callback(string $provider, Request $request): JsonResponse
    {
        $code = (string) $request->input('code', '');
        $redirectUri = (string) $request->input('redirect_uri', '');

        if ($code === '') {
            return response()->json(['error' => 'Missing authorization code'], 422);
        }

        $profile = $this->registry->get($provider)->exchangeCode($code, $redirectUri);
        $user = $this->linker->findOrCreateUser($profile);

        $token = $user->createToken('sso:'.$provider)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
            ],
        ]);
    }
}
