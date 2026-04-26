<?php

declare(strict_types=1);

use App\Application\Billing\Services\BillingGateway;
use App\Application\Billing\Services\BillingWebhookProcessor;
use App\Infrastructure\Billing\IdempotentBillingWebhookProcessor;
use App\Infrastructure\Billing\PlaceholderStripeBillingGateway;
use App\Models\BillingCoupon;
use App\Models\BillingCustomer;
use App\Models\BillingPlan;
use App\Models\BillingSubscription;
use App\Models\BillingWebhookEvent;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- DI bindings ----------------------------------------------------------

it('binds BillingGateway to the placeholder Stripe adapter', function (): void {
    expect(app(BillingGateway::class))->toBeInstanceOf(PlaceholderStripeBillingGateway::class);
});

it('binds BillingWebhookProcessor to the idempotent intake', function (): void {
    expect(app(BillingWebhookProcessor::class))->toBeInstanceOf(IdempotentBillingWebhookProcessor::class);
});

// ---- Domain helpers --------------------------------------------------------

it('BillingSubscription::isActive() is true for trialing/active', function (string $status, bool $expected): void {
    $sub = new BillingSubscription(['status' => $status]);
    expect($sub->isActive())->toBe($expected);
})->with([
    [BillingSubscription::STATUS_TRIALING, true],
    [BillingSubscription::STATUS_ACTIVE, true],
    [BillingSubscription::STATUS_PAST_DUE, false],
    [BillingSubscription::STATUS_CANCELED, false],
    [BillingSubscription::STATUS_INCOMPLETE, false],
]);

it('BillingCoupon::isRedeemable() respects expires_at and max_redemptions', function (): void {
    $expired = new BillingCoupon(['is_active' => true, 'expires_at' => now()->subDay()]);
    $exhausted = new BillingCoupon(['is_active' => true, 'max_redemptions' => 5, 'redeemed_count' => 5]);
    $usable = new BillingCoupon(['is_active' => true, 'max_redemptions' => 10, 'redeemed_count' => 2]);
    $disabled = new BillingCoupon(['is_active' => false]);

    expect($expired->isRedeemable())->toBeFalse()
        ->and($exhausted->isRedeemable())->toBeFalse()
        ->and($usable->isRedeemable())->toBeTrue()
        ->and($disabled->isRedeemable())->toBeFalse();
});

// ---- PlaceholderStripeBillingGateway ---------------------------------------

it('createCheckoutSession creates an active subscription and synthetic IDs', function (): void {
    [$customer, $plan] = makeCustomerAndPlan();

    $session = app(BillingGateway::class)->createCheckoutSession($customer, $plan);

    expect($session->provider)->toBe('stripe')
        ->and($session->sessionId)->toStartWith('cs_test_')
        ->and($session->redirectUrl)->toStartWith('https://checkout.stripe.test/');

    $sub = BillingSubscription::query()
        ->where('customer_id', $customer->getKey())
        ->where('plan_id', $plan->getKey())
        ->firstOrFail();

    expect($sub->status)->toBe(BillingSubscription::STATUS_ACTIVE)
        ->and($sub->stripe_subscription_id)->toStartWith('sub_test_')
        ->and($sub->current_period_end)->not->toBeNull();

    expect($customer->fresh()->stripe_customer_id)->toStartWith('cus_test_');
});

it('cancelSubscription with atPeriodEnd=true keeps it active until period_end', function (): void {
    [$customer, $plan] = makeCustomerAndPlan();
    app(BillingGateway::class)->createCheckoutSession($customer, $plan);

    $sub = BillingSubscription::query()->where('customer_id', $customer->getKey())->firstOrFail();
    app(BillingGateway::class)->cancelSubscription($sub, atPeriodEnd: true);

    $sub->refresh();
    expect($sub->cancel_at_period_end)->toBeTrue()
        ->and($sub->canceled_at)->not->toBeNull()
        ->and($sub->status)->toBe(BillingSubscription::STATUS_ACTIVE)
        ->and($sub->ended_at)->toBeNull();
});

it('cancelSubscription with atPeriodEnd=false ends it immediately', function (): void {
    [$customer, $plan] = makeCustomerAndPlan();
    app(BillingGateway::class)->createCheckoutSession($customer, $plan);

    $sub = BillingSubscription::query()->where('customer_id', $customer->getKey())->firstOrFail();
    app(BillingGateway::class)->cancelSubscription($sub, atPeriodEnd: false);

    $sub->refresh();
    expect($sub->status)->toBe(BillingSubscription::STATUS_CANCELED)
        ->and($sub->ended_at)->not->toBeNull();
});

// ---- IdempotentBillingWebhookProcessor -------------------------------------

it('webhook processor stores a fresh event row on first delivery', function (): void {
    $payload = ['id' => 'evt_test_001', 'type' => 'invoice.paid', 'data' => ['object' => []]];

    $result = app(BillingWebhookProcessor::class)->process('stripe', $payload);

    expect($result->accepted)->toBeTrue()
        ->and($result->idempotent)->toBeFalse()
        ->and($result->eventId)->toBe('evt_test_001');

    expect(BillingWebhookEvent::query()->where('event_id', 'evt_test_001')->exists())->toBeTrue();
});

it('webhook processor is idempotent on the (provider, event_id) pair', function (): void {
    $payload = ['id' => 'evt_test_002', 'type' => 'invoice.paid'];

    app(BillingWebhookProcessor::class)->process('stripe', $payload);
    $second = app(BillingWebhookProcessor::class)->process('stripe', $payload);

    expect($second->accepted)->toBeTrue()
        ->and($second->idempotent)->toBeTrue();

    expect(BillingWebhookEvent::query()->where('event_id', 'evt_test_002')->count())->toBe(1);
});

it('webhook processor rejects payload without an event id', function (): void {
    $result = app(BillingWebhookProcessor::class)->process('stripe', ['type' => 'invoice.paid']);

    expect($result->accepted)->toBeFalse()
        ->and($result->errorMessage)->toContain('Missing event id');
});

// ---- HTTP webhook endpoint -------------------------------------------------

it('POST /api/v1/billing/webhooks/stripe accepts and persists', function (): void {
    $response = $this->postJson('/api/v1/billing/webhooks/stripe', [
        'id' => 'evt_http_001',
        'type' => 'customer.subscription.created',
    ]);

    $response->assertOk()
        ->assertJson(['accepted' => true, 'idempotent' => false, 'event_id' => 'evt_http_001']);

    expect(BillingWebhookEvent::query()->where('event_id', 'evt_http_001')->exists())->toBeTrue();
});

it('POST /api/v1/billing/webhooks/stripe is safe on retries', function (): void {
    $payload = ['id' => 'evt_http_002', 'type' => 'invoice.paid'];

    $this->postJson('/api/v1/billing/webhooks/stripe', $payload)->assertOk();
    $this->postJson('/api/v1/billing/webhooks/stripe', $payload)
        ->assertOk()
        ->assertJson(['accepted' => true, 'idempotent' => true]);

    expect(BillingWebhookEvent::query()->where('event_id', 'evt_http_002')->count())->toBe(1);
});

// ---- Filament admin --------------------------------------------------------

it('admin reaches /admin/billing-plans index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/billing-plans')->assertOk();
});

it('admin reaches /admin/billing-plans/create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/billing-plans/create')->assertOk();
});

it('admin reaches /admin/billing-subscriptions index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/billing-subscriptions')->assertOk();
});

it('admin reaches /admin/billing-invoices index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/billing-invoices')->assertOk();
});

it('admin reaches /admin/billing-coupons index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/billing-coupons')->assertOk();
});

// ---- helpers ---------------------------------------------------------------

/**
 * @return array{0: BillingCustomer, 1: BillingPlan}
 */
function makeCustomerAndPlan(): array
{
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'b1', 'name' => 'B1', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);

    $customer = BillingCustomer::query()->create([
        'project_id' => $project->id,
        'user_id' => $owner->getKey(),
        'email' => $owner->email,
    ]);

    $plan = BillingPlan::query()->create([
        'project_id' => $project->id,
        'slug' => 'pro',
        'name' => 'Pro',
        'price_cents' => 2900,
        'currency' => 'EUR',
        'billing_cycle' => BillingPlan::CYCLE_MONTHLY,
        'is_active' => true,
    ]);

    return [$customer, $plan];
}
