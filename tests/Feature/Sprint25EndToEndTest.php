<?php

declare(strict_types=1);

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Application\Communication\Services\NotificationDispatcher;
use App\Application\Identity\Services\TeamService;
use App\Application\Operations\Services\BackupRunner;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Models\Article;
use App\Models\AutomationRequest;
use App\Models\Backup;
use App\Models\BillingWebhookEvent;
use App\Models\Faq;
use App\Models\NotificationDispatch;
use App\Models\NotificationTemplate;
use App\Models\Page;
use App\Models\Project as EloquentProject;
use App\Models\TeamInvitation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ========================================================================
// PHASE FINALE — End-to-end production-readiness verification.
//
// This single test exercises the entire WebFactory stack in one sequence,
// catching any regression in the cross-BC wiring that per-feature tests
// could miss.
// ========================================================================

it('runs the full WebFactory stack end-to-end without error', function (): void {
    config(['queue.default' => 'sync']);
    Storage::fake('s3');

    // ------ 1. Pipeline: create a project, verify it reaches `deployed` --
    $owner = User::factory()->create(['email' => 'e2e-owner@webfactory.test']);
    $owner->assignRole('admin');

    $project = app(CreateProjectHandler::class)->handle(new CreateProjectCommand(
        slug: 'e2e-full',
        name: 'E2E Full Stack',
        description: str_repeat('a no-code AI tool for ', 12),
        locale: 'fr-FR',
        primaryDomain: 'e2e.local',
        ownerId: (string) $owner->getKey(),
        metadata: ['target_locales' => ['fr-FR', 'en-US']],
    ));

    $row = EloquentProject::query()->find($project->id);
    expect($row)->not->toBeNull()
        ->and($row->status)->toBe(ProjectStatus::Deployed->value);

    // ------ 2. All 7 pipeline metadata keys present ------------------------
    $meta = (array) $row->metadata;
    foreach (['analysis', 'blueprint', 'design', 'brief', 'brief_score', 'github', 'content', 'deployment'] as $key) {
        expect(array_key_exists($key, $meta))->toBeTrue();
    }

    // ------ 3. Content tables populated for both locales -------------------
    expect(Page::query()->where('project_id', $row->id)->where('locale', 'fr-FR')->exists())->toBeTrue()
        ->and(Page::query()->where('project_id', $row->id)->where('locale', 'en-US')->exists())->toBeTrue()
        ->and(Article::query()->where('project_id', $row->id)->where('is_pillar', true)->exists())->toBeTrue()
        ->and(Faq::query()->where('project_id', $row->id)->count())->toBeGreaterThan(0);

    // ------ 4. Public surface: home + manifest + sw.js + healthcheck ------
    $this->get('/')->assertOk()->assertSee('aeo-answer', escape: false);
    $this->get('/manifest.webmanifest')->assertOk();
    $this->get('/sw.js')->assertOk();
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    // ------ 5. Security headers fire on every response ---------------------
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    // ------ 6. Public CTA modal endpoint --------------------------------
    $this->postJson('/api/v1/automation-requests', [
        'first_name' => 'E2E',
        'last_name' => 'Tester',
        'email' => 'e2e-lead@webfactory.test',
        'phone_country_code' => '+33',
        'phone_number' => '600000000',
        'category' => 'b2c',
        'message' => 'Full-stack end-to-end smoke test from Pest.',
        'rgpd_accepted' => true,
    ])->assertStatus(201);

    expect(AutomationRequest::query()->where('email', 'e2e-lead@webfactory.test')->exists())->toBeTrue();

    // ------ 7. Stripe webhook idempotency ---------------------------------
    $payload = ['id' => 'evt_e2e_001', 'type' => 'invoice.paid'];
    $this->postJson('/api/v1/billing/webhooks/stripe', $payload)->assertOk();
    $this->postJson('/api/v1/billing/webhooks/stripe', $payload)
        ->assertOk()
        ->assertJson(['idempotent' => true]);
    expect(BillingWebhookEvent::query()->where('event_id', 'evt_e2e_001')->count())->toBe(1);

    // ------ 8. SSO redirect URL + callback flow ---------------------------
    $this->getJson('/api/v1/auth/sso/google/redirect')
        ->assertOk()
        ->assertJsonStructure(['authorization_url', 'state']);

    $this->postJson('/api/v1/auth/sso/google/callback', [
        'code' => 'sso_test:google_e2e_42:e2e-sso@webfactory.test',
    ])->assertOk()
        ->assertJsonPath('user.email', 'e2e-sso@webfactory.test');

    // ------ 9. Notifications dispatcher honors templates + opt-outs -------
    NotificationTemplate::query()->create([
        'event_type' => 'order.placed',
        'channel' => 'email',
        'locale' => 'en',
        'subject' => 'Order confirmed',
        'body' => 'Hi {{ name }}, your order ships soon.',
        'is_active' => true,
    ]);

    app(NotificationDispatcher::class)->dispatch(
        user: $owner,
        eventType: 'order.placed',
        channel: 'email',
        recipient: $owner->email,
        payload: ['name' => 'Owner'],
    );

    expect(NotificationDispatch::query()
        ->where('user_id', $owner->getKey())
        ->where('event_type', 'order.placed')
        ->where('status', NotificationDispatch::STATUS_SENT)
        ->exists())->toBeTrue();

    // ------ 10. Backup runner produces a succeeded row --------------------
    $backup = app(BackupRunner::class)
        ->run(Backup::KIND_FULL, $row->id);
    expect($backup->status)->toBe(Backup::STATUS_SUCCEEDED);

    // ------ 11. Teams creation + invitation -------------------------------
    $team = app(TeamService::class)
        ->createTeam($owner, 'E2E Team');
    expect($team->name)->toBe('E2E Team');

    $invitation = app(TeamService::class)
        ->inviteMember($team, $owner, 'invitee@webfactory.test');
    expect($invitation['raw_token'])->toBeString()
        ->and($invitation['invitation']->status)->toBe(TeamInvitation::STATUS_PENDING);

    // ------ 12. Filament admin routes (every group) -----------------------
    foreach ([
        '/admin/users', '/admin/projects', '/admin/teams', '/admin/team-invitations',
        '/admin/sso-identities', '/admin/pages', '/admin/articles', '/admin/faqs',
        '/admin/news', '/admin/billing-plans', '/admin/billing-subscriptions',
        '/admin/billing-invoices', '/admin/billing-coupons',
        '/admin/notification-templates', '/admin/notification-dispatches',
        '/admin/onboarding-flows', '/admin/user-onboarding-progress',
        '/admin/automation-requests', '/admin/backups',
        '/admin/manage-appearance-settings', '/admin/seo-hub',
    ] as $url) {
        $this->actingAs($owner)->get($url)
            ->assertOk("Admin route {$url} did not return 200");
    }
})->skip(false);
