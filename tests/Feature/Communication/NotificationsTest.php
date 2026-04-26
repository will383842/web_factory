<?php

declare(strict_types=1);

use App\Application\Communication\Services\NotificationChannel;
use App\Application\Communication\Services\NotificationChannelRegistry;
use App\Application\Communication\Services\NotificationDispatcher;
use App\Infrastructure\Communication\LogNotificationChannel;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- Registry --------------------------------------------------------------

it('registers 9 placeholder notification channels', function (): void {
    $registry = app(NotificationChannelRegistry::class);
    expect($registry->names())->toBe([
        'in_app', 'email', 'sms', 'whatsapp',
        'push_web', 'push_mob', 'telegram', 'slack', 'discord',
    ]);
    expect($registry->get('email'))->toBeInstanceOf(NotificationChannel::class)
        ->and($registry->get('email'))->toBeInstanceOf(LogNotificationChannel::class);
});

it('registry throws on unknown channel', function (): void {
    expect(fn () => app(NotificationChannelRegistry::class)->get('myspace'))
        ->toThrow(InvalidArgumentException::class);
});

// ---- NotificationTemplate::render() ---------------------------------------

it('NotificationTemplate::render() substitutes placeholders', function (): void {
    $tpl = new NotificationTemplate(['body' => 'Hello {{ name }}, your invoice is {{ amount }}.']);
    $rendered = $tpl->render(['name' => 'Alice', 'amount' => '€29.00']);
    expect($rendered)->toBe('Hello Alice, your invoice is €29.00.');
});

// ---- NotificationDispatcher -----------------------------------------------

it('dispatches and logs a sent row when template + channel exist', function (): void {
    $user = User::factory()->create();
    $tpl = NotificationTemplate::query()->create([
        'event_type' => 'billing.subscription.started',
        'channel' => 'email',
        'locale' => 'en',
        'subject' => 'Welcome',
        'body' => 'Hi {{ name }}',
        'is_active' => true,
    ]);

    $row = app(NotificationDispatcher::class)->dispatch(
        user: $user,
        eventType: 'billing.subscription.started',
        channel: 'email',
        recipient: $user->email,
        payload: ['name' => 'Alice'],
        locale: 'en',
    );

    expect($row->status)->toBe(NotificationDispatch::STATUS_SENT)
        ->and($row->template_id)->toBe($tpl->getKey())
        ->and($row->external_id)->toStartWith('email_')
        ->and($row->sent_at)->not->toBeNull();
});

it('skips when user opted out and event is not transactional', function (): void {
    $user = User::factory()->create();
    NotificationTemplate::query()->create([
        'event_type' => 'newsletter.weekly',
        'channel' => 'email',
        'locale' => 'en',
        'body' => 'News',
        'is_active' => true,
    ]);
    NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'channel' => 'email',
        'event_type' => 'newsletter.weekly',
        'enabled' => false,
    ]);

    $row = app(NotificationDispatcher::class)->dispatch(
        user: $user,
        eventType: 'newsletter.weekly',
        channel: 'email',
        recipient: $user->email,
    );

    expect($row->status)->toBe(NotificationDispatch::STATUS_SKIPPED)
        ->and($row->error_message)->toBe('User opted out');
});

it('bypasses opt-out for transactional events', function (): void {
    $user = User::factory()->create();
    NotificationTemplate::query()->create([
        'event_type' => 'auth.password_reset',
        'channel' => 'email',
        'locale' => 'en',
        'body' => 'Reset link',
        'is_active' => true,
    ]);
    NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'channel' => 'email',
        'event_type' => 'auth.password_reset',
        'enabled' => false,
    ]);

    $row = app(NotificationDispatcher::class)->dispatch(
        user: $user,
        eventType: 'auth.password_reset',
        channel: 'email',
        recipient: $user->email,
    );

    expect($row->status)->toBe(NotificationDispatch::STATUS_SENT);
});

it('records failed status when no template is found', function (): void {
    $user = User::factory()->create();

    $row = app(NotificationDispatcher::class)->dispatch(
        user: $user,
        eventType: 'unknown.event',
        channel: 'sms',
        recipient: '+33600000000',
    );

    expect($row->status)->toBe(NotificationDispatch::STATUS_FAILED)
        ->and($row->error_message)->toBe('No active template');
});

it('prefers project-scoped template over platform-wide', function (): void {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'p1', 'name' => 'P1', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);

    $platform = NotificationTemplate::query()->create([
        'event_type' => 'order.placed', 'channel' => 'email', 'locale' => 'en',
        'body' => 'Platform default', 'is_active' => true,
    ]);
    $scoped = NotificationTemplate::query()->create([
        'project_id' => $project->id,
        'event_type' => 'order.placed', 'channel' => 'email', 'locale' => 'en',
        'body' => 'Project specific', 'is_active' => true,
    ]);

    $row = app(NotificationDispatcher::class)->dispatch(
        user: $user,
        eventType: 'order.placed',
        channel: 'email',
        recipient: $user->email,
        projectId: $project->id,
    );

    expect($row->template_id)->toBe($scoped->getKey())
        ->and($row->template_id)->not->toBe($platform->getKey());
});

// ---- Filament admin --------------------------------------------------------

it('admin reaches /admin/notification-templates index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/notification-templates')->assertOk();
});

it('admin reaches /admin/notification-templates/create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/notification-templates/create')->assertOk();
});

it('admin reaches /admin/notification-dispatches index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/notification-dispatches')->assertOk();
});
