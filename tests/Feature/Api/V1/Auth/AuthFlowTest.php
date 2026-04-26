<?php

declare(strict_types=1);

use App\Models\MagicLinkToken;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- Register --------------------------------------------------------------

it('registers a new user via POST /api/v1/auth/register and returns a Sanctum token', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    $alice = User::query()->where('email', 'alice@example.com')->first();
    expect($alice->hasRole('user'))->toBeTrue();
});

it('rejects weak passwords on register (422)', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects duplicate email on register', function (): void {
    User::factory()->create(['email' => 'dup@example.com']);
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dup',
        'email' => 'dup@example.com',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// ---- Login / Logout / Me ---------------------------------------------------

it('logs a user in with valid credentials and returns a Sanctum token', function (): void {
    User::factory()->create(['email' => 'carol@example.com', 'password' => Hash::make('StrongPass123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'carol@example.com',
        'password' => 'StrongPass123',
    ]);

    $response->assertOk()->assertJsonStructure(['token', 'user']);
});

it('rejects invalid credentials with 422', function (): void {
    User::factory()->create(['email' => 'd@example.com', 'password' => Hash::make('StrongPass123')]);
    $this->postJson('/api/v1/auth/login', [
        'email' => 'd@example.com',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('returns the authenticated user via GET /api/v1/auth/me', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('two_factor_enabled', false);
});

it('logs the user out and revokes the current Sanctum token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

// ---- Password reset --------------------------------------------------------

it('accepts a forgot-password request without leaking whether the email exists', function (): void {
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If the email exists, a reset link has been sent.');
});

// ---- Magic link ------------------------------------------------------------

it('issues a magic link, then consumes it for a Sanctum token', function (): void {
    $user = User::factory()->create(['email' => 'magic@example.com']);

    $this->postJson('/api/v1/auth/magic-link/request', ['email' => 'magic@example.com'])
        ->assertOk();

    $row = MagicLinkToken::query()->where('user_id', $user->getKey())->latest('id')->first();
    expect($row)->not->toBeNull();

    $consume = $this->getJson('/api/v1/auth/magic-link/consume?token='.$row->token);
    $consume->assertOk()->assertJsonStructure(['token', 'user']);

    expect($row->fresh()->consumed_at)->not->toBeNull();
});

it('rejects an already-consumed magic link token', function (): void {
    $user = User::factory()->create();
    $row = MagicLinkToken::query()->create([
        'user_id' => $user->getKey(),
        'token' => str_repeat('a', 64),
        'expires_at' => now()->addMinutes(60),
        'consumed_at' => now(),
    ]);

    $this->getJson('/api/v1/auth/magic-link/consume?token='.$row->token)
        ->assertStatus(422);
});

it('rejects an expired magic link token', function (): void {
    $user = User::factory()->create();
    $row = MagicLinkToken::query()->create([
        'user_id' => $user->getKey(),
        'token' => str_repeat('b', 64),
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/v1/auth/magic-link/consume?token='.$row->token)
        ->assertStatus(422);
});

// ---- 2FA flow --------------------------------------------------------------

it('enables 2FA, returns a secret + QR + recovery codes, and gates panel until confirmed', function (): void {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/2fa/enable');

    $response->assertOk()
        ->assertJsonStructure(['secret', 'qr_svg_base64', 'recovery_codes']);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeEmpty()
        ->and($user->hasTwoFactorEnabled())->toBeFalse(); // still unconfirmed
});

it('confirms 2FA with a valid TOTP code', function (): void {
    $user = User::factory()->create();
    $google = app(Google2FA::class);
    $secret = $google->generateSecretKey();
    $user->two_factor_secret = $secret;
    $user->two_factor_recovery_codes = ['code1', 'code2'];
    $user->save();

    $code = $google->getCurrentOtp($secret);
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/2fa/confirm', ['code' => $code])
        ->assertOk();

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('login of a 2FA-enabled user returns a challenge_token instead of a Sanctum token', function (): void {
    $user = User::factory()->create([
        'email' => '2fa@example.com',
        'password' => Hash::make('StrongPass123'),
    ]);
    $google = app(Google2FA::class);
    $user->two_factor_secret = $google->generateSecretKey();
    $user->two_factor_recovery_codes = ['x'];
    $user->two_factor_confirmed_at = now();
    $user->save();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => '2fa@example.com',
        'password' => 'StrongPass123',
    ]);

    $response->assertOk()
        ->assertJsonPath('two_factor', true)
        ->assertJsonStructure(['challenge_token']);
});

it('disables 2FA when the password is reconfirmed', function (): void {
    $user = User::factory()->create(['password' => Hash::make('StrongPass123')]);
    $user->two_factor_secret = 'abc';
    $user->two_factor_recovery_codes = ['x'];
    $user->two_factor_confirmed_at = now();
    $user->save();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/2fa/disable', ['password' => 'StrongPass123'])
        ->assertOk();

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});
