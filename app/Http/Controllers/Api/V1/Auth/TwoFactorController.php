<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * 2FA TOTP endpoints.
 *
 *  POST /api/v1/auth/2fa/enable    -> returns secret + QR (svg/base64) + recovery codes
 *  POST /api/v1/auth/2fa/confirm   -> validates first TOTP code, sets two_factor_confirmed_at
 *  POST /api/v1/auth/2fa/verify    -> exchanges a "challenge_token" + TOTP for a Sanctum token
 *  POST /api/v1/auth/2fa/disable   -> requires password reconfirm
 */
final class TwoFactorController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $secret = $this->google2fa->generateSecretKey(32);
        /** @var list<string> $recovery */
        $recovery = array_values(collect(range(1, 8))->map(static fn (): string => Str::lower(Str::random(10)))->all());

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recovery;
        $user->two_factor_confirmed_at = null; // not confirmed until /confirm succeeds
        $user->save();

        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            (string) $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);
        $writer = new Writer($renderer);
        $svg = $writer->writeString($otpAuthUrl);

        return response()->json([
            'secret' => $secret,
            'qr_svg_base64' => base64_encode($svg),
            'recovery_codes' => $recovery,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        /** @var User $user */
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            throw ValidationException::withMessages(['code' => '2FA is not enabled.']);
        }

        if (! $this->google2fa->verifyKey((string) $user->two_factor_secret, (string) $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid TOTP code.']);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json(['message' => '2FA confirmed.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->whereHas('tokens', static fn ($q) => $q->where('token', hash('sha256', explode('|', (string) $data['challenge_token'], 2)[1] ?? '')))
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages(['challenge_token' => 'Invalid challenge token.']);
        }

        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages(['challenge_token' => 'User does not have 2FA enabled.']);
        }

        if (! $this->google2fa->verifyKey((string) $user->two_factor_secret, (string) $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid TOTP code.']);
        }

        // Burn the challenge token + issue a real one.
        $user->tokens()->where('name', '2fa-challenge')->delete();

        $token = $user->createToken((string) ($data['device_name'] ?? 'default'))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at']),
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $data['password'], (string) $user->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json(['message' => '2FA disabled.']);
    }
}
