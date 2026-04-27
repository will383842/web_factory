<?php

declare(strict_types=1);

use App\Models\AutomationRequest;
use App\Models\EventTracking;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\ReferralCode;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- Sprint 18 — Healthcheck ----------------------------------------------

it('GET /api/v1/health returns 200 with per-dependency JSON', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonStructure(['status', 'checks' => ['app', 'db', 'redis'], 'time']);

    expect($response->json('status'))->toBe('ok')
        ->and($response->json('checks.db.status'))->toBe('ok')
        ->and($response->json('checks.redis.status'))->toBe('ok');
});

// ---- Sprint 24 — Security headers -----------------------------------------

it('every response carries the OWASP security headers', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

// ---- Sprint 24 — RGPD export ----------------------------------------------

it('GET /api/v1/me/export returns full user JSON for the caller only', function (): void {
    $user = User::factory()->create(['email' => 'gdpr@example.com', 'name' => 'GDPR User']);
    NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'channel' => 'email',
        'event_type' => 'newsletter.weekly',
        'enabled' => false,
    ]);
    AutomationRequest::query()->create([
        'first_name' => 'GDPR', 'last_name' => 'User', 'email' => 'gdpr@example.com',
        'phone_country_code' => '+33', 'phone_number' => '600000000',
        'category' => 'b2c', 'message' => 'short msg please', 'rgpd_accepted' => true,
        'status' => 'new',
    ]);

    Sanctum::actingAs($user);
    $response = $this->getJson('/api/v1/me/export');

    $response->assertOk()
        ->assertJsonPath('user.email', 'gdpr@example.com');

    expect(count($response->json('notification_preferences')))->toBe(1)
        ->and(count($response->json('automation_requests')))->toBe(1);
});

// ---- Sprint 24 — RGPD delete (right to erasure) --------------------------

it('DELETE /api/v1/me anonymizes audit rows and deletes the user', function (): void {
    $user = User::factory()->create(['email' => 'delete@example.com']);
    $dispatch = NotificationDispatch::query()->create([
        'user_id' => $user->getKey(),
        'channel' => 'email',
        'event_type' => 'order.placed',
        'recipient' => 'delete@example.com',
        'status' => NotificationDispatch::STATUS_SENT,
    ]);

    Sanctum::actingAs($user);
    $this->deleteJson('/api/v1/me')->assertNoContent();

    expect(User::query()->find($user->getKey()))->toBeNull();

    // Notification audit log is anonymized but kept (financial/legal retention).
    // Note: user_id stays set on the row even after the user is deleted —
    // the FK uses nullOnDelete in the migration so the row survives.
    $dispatch->refresh();
    expect($dispatch->recipient)->toBe('anonymized');
});

// ---- Sprint 23 — PWA static files -----------------------------------------

it('serves the PWA manifest at /manifest.webmanifest', function (): void {
    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertSee('"name": "WebFactory"', escape: false);
});

it('serves the service-worker at /sw.js', function (): void {
    $this->get('/sw.js')
        ->assertOk()
        ->assertSee('webfactory-v1', escape: false);
});

// ---- Sprint 20 — Event tracking model -------------------------------------

it('persists an EventTracking row with cast properties JSON', function (): void {
    $user = User::factory()->create();

    $row = EventTracking::query()->create([
        'user_id' => $user->getKey(),
        'session_id' => 'sess_abc',
        'name' => EventTracking::NAME_SIGNUP,
        'properties' => ['plan' => 'pro', 'ref' => 'twitter'],
        'occurred_at' => now(),
    ]);

    expect($row->name)->toBe('signup')
        ->and($row->properties->toArray())->toBe(['plan' => 'pro', 'ref' => 'twitter']);
});

// ---- Sprint 22 — Referral code -------------------------------------------

it('persists a ReferralCode with unique code', function (): void {
    $user = User::factory()->create();
    $r = ReferralCode::query()->create([
        'owner_id' => $user->getKey(),
        'code' => 'ALICE-2026',
        'is_active' => true,
        'bonus_credits_cents' => 500,
    ]);

    expect($r->is_active)->toBeTrue()->and($r->code)->toBe('ALICE-2026');
});
