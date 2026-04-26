<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET  /api/v1/auth/email/verify/{id}/{hash}  — signed URL handler
 * POST /api/v1/auth/email/resend
 */
final class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link.'], 403);
        }

        /** @var User|null $user */
        $user = User::query()->find($id);
        if ($user === null) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (! hash_equals(sha1((string) $user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Hash mismatch.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}
