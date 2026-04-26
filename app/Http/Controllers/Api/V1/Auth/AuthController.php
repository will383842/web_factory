<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Public B2C auth API (Sprint 6.5).
 *
 * Issues Sanctum personal access tokens. End users of WebFactory-generated
 * platforms hit these endpoints from their SPA / mobile app.
 */
final class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     *
     * Creates a "user" (Spatie role), fires registered event so the
     * MustVerifyEmail flow sends the verification mail.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $user->assignRole('user');
        event(new Registered($user));

        $token = $user->createToken($request->string('device_name', 'default')->toString())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at']),
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     *
     * If the user has 2FA enabled, the response carries a "two_factor"
     * challenge token instead of a Sanctum token; the caller must POST that
     * token + a valid TOTP code to {@see TwoFactorController::verify()} to
     * exchange it for a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt(['email' => $request->string('email')->toString(), 'password' => $request->string('password')->toString()])) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            // Issue a short-lived "challenge" token that only TwoFactorController accepts.
            $challenge = $user->createToken('2fa-challenge', ['2fa-pending'])->plainTextToken;

            return response()->json([
                'two_factor' => true,
                'challenge_token' => $challenge,
            ]);
        }

        $token = $user->createToken($request->string('device_name', 'default')->toString())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at']),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        return response()->json([
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'roles' => $user->getRoleNames(),
        ]);
    }
}
