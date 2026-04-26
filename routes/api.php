<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\MagicLinkController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use App\Http\Controllers\Api\V1\Billing\StripeWebhookController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public webhooks (Sprint 13.1) — no auth, signature verification added Sprint 16
Route::post('v1/billing/webhooks/stripe', StripeWebhookController::class)
    ->name('api.v1.billing.webhooks.stripe');

// Public B2C auth (Sprint 6.5) — no auth required
Route::prefix('v1/auth')->name('api.v1.auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->name('password.forgot');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify')
        ->middleware('signed');

    Route::post('magic-link/request', [MagicLinkController::class, 'request'])->name('magic-link.request');
    Route::get('magic-link/consume', [MagicLinkController::class, 'consume'])->name('magic-link.consume');

    // 2FA verify happens with a challenge_token (no Sanctum guard required)
    Route::post('2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
});

// Authenticated routes (Sanctum)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::prefix('v1/auth')->name('api.v1.auth.')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');

        Route::post('2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
        Route::post('2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
        Route::post('2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    });

    Route::prefix('v1')->name('api.v1.')->group(function (): void {
        Route::apiResource('projects', ProjectController::class)
            ->only(['index', 'show', 'store', 'destroy']);
    });
});
