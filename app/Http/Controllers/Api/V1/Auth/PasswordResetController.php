<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/v1/auth/forgot-password
 * POST /api/v1/auth/reset-password
 */
final class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::ResetLinkSent) {
            // Don't leak whether the email exists — return 200 either way.
        }

        return response()->json(['message' => 'If the email exists, a reset link has been sent.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $data,
            function ($user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete(); // revoke all tokens on password reset
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['token' => __($status)]);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
