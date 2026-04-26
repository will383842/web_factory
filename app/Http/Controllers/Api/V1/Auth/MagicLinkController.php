<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\MagicLinkToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single-use magic-link login.
 *
 *  POST /api/v1/auth/magic-link/request   -> generates a 60-min signed link, emails it
 *  GET  /api/v1/auth/magic-link/consume   -> consumes the token, issues a Sanctum token
 */
final class MagicLinkController extends Controller
{
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if ($user !== null) {
            $token = Str::random(64);
            MagicLinkToken::query()->create([
                'user_id' => $user->getKey(),
                'token' => $token,
                'expires_at' => now()->addMinutes(60),
            ]);

            $url = url('/api/v1/auth/magic-link/consume?token='.$token);
            // Production: use a Notification (Mail). Dev: log it.
            Log::info('Magic link issued', ['user_id' => $user->getKey(), 'url' => $url]);
        }

        // Always 200 to avoid email enumeration.
        return response()->json(['message' => 'If the email exists, a magic link has been sent.']);
    }

    public function consume(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);

        $row = MagicLinkToken::query()->where('token', $data['token'])->first();
        if ($row === null || ! $row->isUsable()) {
            throw ValidationException::withMessages(['token' => 'Invalid or expired link.']);
        }

        $row->consumed_at = now();
        $row->save();

        /** @var User $user */
        $user = $row->user;

        $token = $user->createToken('magic-link')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at']),
        ]);
    }
}
