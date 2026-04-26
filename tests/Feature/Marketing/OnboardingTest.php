<?php

declare(strict_types=1);

use App\Application\Marketing\Services\ActivationScoreCalculator;
use App\Application\Marketing\Services\OnboardingOrchestrator;
use App\Models\OnboardingFlow;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function makeOnboardingFlow(): OnboardingFlow
{
    return OnboardingFlow::query()->create([
        'slug' => 'starter',
        'name' => 'Starter onboarding',
        'audience' => OnboardingFlow::AUDIENCE_USER,
        'is_active' => true,
        'steps' => [
            ['key' => 'profile', 'title' => 'Complete profile', 'weight' => 2],
            ['key' => 'invite_member', 'title' => 'Invite first member', 'weight' => 3],
            ['key' => 'subscribed', 'title' => 'Subscribe', 'weight' => 5],
        ],
    ]);
}

// ---- ActivationScoreCalculator --------------------------------------------

it('returns 0 for an empty completed list', function (): void {
    $flow = makeOnboardingFlow();
    expect(app(ActivationScoreCalculator::class)->calculate($flow, []))->toBe(0);
});

it('returns weighted percentage when some steps are completed', function (): void {
    $flow = makeOnboardingFlow();

    // profile (2) + subscribed (5) = 7 / (2+3+5)=10 → 70%
    $score = app(ActivationScoreCalculator::class)->calculate($flow, ['profile', 'subscribed']);
    expect($score)->toBe(70);
});

it('returns 100 when every step is completed', function (): void {
    $flow = makeOnboardingFlow();
    $score = app(ActivationScoreCalculator::class)->calculate($flow, ['profile', 'invite_member', 'subscribed']);
    expect($score)->toBe(100);
});

it('treats missing weight as 1', function (): void {
    $flow = OnboardingFlow::query()->create([
        'slug' => 'noweights', 'name' => 'NW', 'audience' => 'user',
        'steps' => [['key' => 'a', 'title' => 'A'], ['key' => 'b', 'title' => 'B']],
        'is_active' => true,
    ]);
    expect(app(ActivationScoreCalculator::class)->calculate($flow, ['a']))->toBe(50);
});

// ---- OnboardingOrchestrator -----------------------------------------------

it('start() creates a fresh progress row at score 0', function (): void {
    $user = User::factory()->create();
    $flow = makeOnboardingFlow();

    $progress = app(OnboardingOrchestrator::class)->start($user, $flow);

    expect($progress->score)->toBe(0)
        ->and($progress->started_at)->not->toBeNull()
        ->and($progress->completed_steps->toArray())->toBe([]);
});

it('markStepCompleted() recomputes the score and dedups', function (): void {
    $user = User::factory()->create();
    $flow = makeOnboardingFlow();

    app(OnboardingOrchestrator::class)->markStepCompleted($user, $flow, 'profile');
    $progress = app(OnboardingOrchestrator::class)->markStepCompleted($user, $flow, 'profile'); // duplicate
    expect($progress->completed_steps->toArray())->toBe(['profile'])
        ->and($progress->score)->toBe(20);
});

it('markStepCompleted() sets completed_at when score reaches 100', function (): void {
    $user = User::factory()->create();
    $flow = makeOnboardingFlow();

    app(OnboardingOrchestrator::class)->markStepCompleted($user, $flow, 'profile');
    app(OnboardingOrchestrator::class)->markStepCompleted($user, $flow, 'invite_member');
    $progress = app(OnboardingOrchestrator::class)->markStepCompleted($user, $flow, 'subscribed');

    expect($progress->score)->toBe(100)
        ->and($progress->completed_at)->not->toBeNull();
});

// ---- Filament admin --------------------------------------------------------

it('admin reaches /admin/onboarding-flows index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/onboarding-flows')->assertOk();
});

it('admin reaches /admin/onboarding-flows/create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/onboarding-flows/create')->assertOk();
});

it('admin reaches /admin/user-onboarding-progress index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/user-onboarding-progress')->assertOk();
});
