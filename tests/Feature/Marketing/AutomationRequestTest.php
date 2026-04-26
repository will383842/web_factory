<?php

declare(strict_types=1);

use App\Application\Marketing\Services\AutomationRequestService;
use App\Domain\Marketing\Events\AutomationRequested;
use App\Models\AutomationRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- Application service --------------------------------------------------

it('AutomationRequestService persists row + emits AutomationRequested event', function (): void {
    Event::fake([AutomationRequested::class]);

    $row = app(AutomationRequestService::class)->submit([
        'first_name' => 'Alice',
        'last_name' => 'Martin',
        'email' => 'alice@example.com',
        'phone_country_code' => '+33',
        'phone_number' => '612345678',
        'company' => 'Acme',
        'category' => 'b2c-saas',
        'message' => 'Need help automating quotes.',
        'rgpd_accepted' => true,
        'source' => 'homepage-cta',
    ]);

    expect($row->status)->toBe(AutomationRequest::STATUS_NEW)
        ->and($row->fullName())->toBe('Alice Martin')
        ->and($row->fullPhone())->toBe('+33 612345678');

    Event::assertDispatched(AutomationRequested::class, function (AutomationRequested $e) use ($row): bool {
        return $e->automationRequestId === (int) $row->getKey()
            && $e->email === 'alice@example.com'
            && $e->category === 'b2c-saas';
    });
});

// ---- HTTP endpoint --------------------------------------------------------

it('POST /api/v1/automation-requests creates a new row + 201', function (): void {
    $response = $this->postJson('/api/v1/automation-requests', [
        'first_name' => 'Bob',
        'last_name' => 'Dupont',
        'email' => 'bob@example.com',
        'phone_country_code' => '+33',
        'phone_number' => '612345678',
        'company' => 'Acme',
        'category' => 'b2b-saas',
        'message' => 'I need a 14-day implementation.',
        'rgpd_accepted' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'status', 'message']);

    expect(AutomationRequest::query()->where('email', 'bob@example.com')->exists())->toBeTrue();
});

it('rejects missing rgpd_accepted (422)', function (): void {
    $this->postJson('/api/v1/automation-requests', [
        'first_name' => 'Carol',
        'last_name' => 'Doe',
        'email' => 'carol@example.com',
        'phone_country_code' => '+33',
        'phone_number' => '612345678',
        'category' => 'b2c-saas',
        'message' => 'Need automation help.',
        'rgpd_accepted' => false,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['rgpd_accepted']);
});

it('rejects invalid phone country code', function (): void {
    $this->postJson('/api/v1/automation-requests', [
        'first_name' => 'Dan',
        'last_name' => 'X',
        'email' => 'dan@example.com',
        'phone_country_code' => 'invalid',
        'phone_number' => '123',
        'category' => 'x',
        'message' => 'short message ok',
        'rgpd_accepted' => true,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['phone_country_code']);
});

it('rejects too-short message', function (): void {
    $this->postJson('/api/v1/automation-requests', [
        'first_name' => 'Eve',
        'last_name' => 'Y',
        'email' => 'eve@example.com',
        'phone_country_code' => '+33',
        'phone_number' => '612345678',
        'category' => 'x',
        'message' => 'short',
        'rgpd_accepted' => true,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

// ---- Public Blade rendering -----------------------------------------------

it('GET / renders the public home page with AeoAnswer block', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('WebFactory', escape: false)
        ->assertSee('aeo-answer', escape: false)
        ->assertSee('What does WebFactory do?', escape: false);
});

it('home page exposes the automation modal trigger', function (): void {
    $this->get('/')
        ->assertSee('data-open-automation-modal', escape: false)
        ->assertSee('automation-modal', escape: false);
});

// ---- Filament admin --------------------------------------------------------

it('admin reaches /admin/automation-requests index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/automation-requests')->assertOk();
});
