<?php

declare(strict_types=1);

use App\Application\Identity\DTOs\SsoUserProfile;
use App\Application\Identity\Services\SsoIdentityLinker;
use App\Application\Identity\Services\SsoProvider;
use App\Application\Identity\Services\SsoProviderRegistry;
use App\Application\Identity\Services\TeamService;
use App\Infrastructure\Identity\PlaceholderSsoProvider;
use App\Models\SsoIdentity;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- DI bindings -----------------------------------------------------------

it('registers 5 placeholder SSO providers in the registry', function (): void {
    $registry = app(SsoProviderRegistry::class);
    expect($registry->names())->toBe(['google', 'microsoft', 'apple', 'okta', 'github']);
    expect($registry->get('google'))->toBeInstanceOf(SsoProvider::class)
        ->and($registry->get('google'))->toBeInstanceOf(PlaceholderSsoProvider::class);
});

it('throws on unknown provider', function (): void {
    expect(fn () => app(SsoProviderRegistry::class)->get('myspace'))
        ->toThrow(InvalidArgumentException::class);
});

// ---- TeamService -----------------------------------------------------------

it('createTeam creates a team and an owner membership', function (): void {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->createTeam($owner, 'Acme Corp');

    expect($team->slug)->toStartWith('acme-corp-')
        ->and($team->owner_id)->toBe($owner->getKey());

    expect(TeamMember::query()
        ->where('team_id', $team->getKey())
        ->where('user_id', $owner->getKey())
        ->where('role', Team::ROLE_OWNER)
        ->exists())->toBeTrue();
});

it('inviteMember creates a pending invitation with hashed token', function (): void {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->createTeam($owner, 'Acme');

    $result = app(TeamService::class)->inviteMember($team, $owner, 'jane@acme.test');

    expect($result['raw_token'])->toBeString()
        ->and(strlen($result['raw_token']))->toBe(48);

    /** @var TeamInvitation $inv */
    $inv = $result['invitation'];
    expect($inv->status)->toBe(TeamInvitation::STATUS_PENDING)
        ->and($inv->token_hash)->toBe(TeamInvitation::hashToken($result['raw_token']))
        ->and($inv->expires_at->isFuture())->toBeTrue();
});

it('acceptInvitation marks it accepted and creates the member', function (): void {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->createTeam($owner, 'Acme');
    $result = app(TeamService::class)->inviteMember($team, $owner, 'jane@acme.test');

    $jane = User::factory()->create(['email' => 'jane@acme.test']);
    $member = app(TeamService::class)->acceptInvitation($result['raw_token'], $jane);

    expect($member->team_id)->toBe($team->getKey())
        ->and($member->role)->toBe(Team::ROLE_MEMBER);

    expect($result['invitation']->fresh()->status)->toBe(TeamInvitation::STATUS_ACCEPTED);
});

it('acceptInvitation rejects an expired token', function (): void {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->createTeam($owner, 'Acme');
    $result = app(TeamService::class)->inviteMember($team, $owner, 'jane@acme.test');

    $result['invitation']->forceFill(['expires_at' => now()->subMinute()])->save();

    $jane = User::factory()->create();

    expect(fn () => app(TeamService::class)->acceptInvitation($result['raw_token'], $jane))
        ->toThrow(DomainException::class);
});

it('transferOwnership demotes previous owner to admin and promotes new', function (): void {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->createTeam($owner, 'Acme');

    $newOwner = User::factory()->create();
    $team = app(TeamService::class)->transferOwnership($team, $newOwner);

    expect($team->owner_id)->toBe($newOwner->getKey());

    $oldOwnerMembership = TeamMember::query()
        ->where('team_id', $team->getKey())
        ->where('user_id', $owner->getKey())
        ->first();
    $newOwnerMembership = TeamMember::query()
        ->where('team_id', $team->getKey())
        ->where('user_id', $newOwner->getKey())
        ->first();

    expect($oldOwnerMembership->role)->toBe(Team::ROLE_ADMIN)
        ->and($newOwnerMembership->role)->toBe(Team::ROLE_OWNER);
});

// ---- SsoIdentityLinker -----------------------------------------------------

it('SsoIdentityLinker creates a fresh user when email is unknown', function (): void {
    $profile = new SsoUserProfile(
        provider: 'google',
        providerUserId: 'google_123',
        email: 'newuser@gmail.test',
        name: 'New User',
        accessToken: 'tok_x',
        refreshToken: 'ref_x',
        expiresIn: 3600,
        rawPayload: ['hd' => 'gmail.test'],
    );

    $user = app(SsoIdentityLinker::class)->findOrCreateUser($profile);

    expect($user->email)->toBe('newuser@gmail.test')
        ->and($user->email_verified_at)->not->toBeNull();

    expect(SsoIdentity::query()
        ->where('user_id', $user->getKey())
        ->where('provider', 'google')
        ->where('provider_user_id', 'google_123')
        ->exists())->toBeTrue();
});

it('SsoIdentityLinker links to an existing email-matching user', function (): void {
    $existing = User::factory()->create(['email' => 'existing@test.com']);

    $profile = new SsoUserProfile(
        provider: 'microsoft',
        providerUserId: 'ms_456',
        email: 'existing@test.com',
    );

    $user = app(SsoIdentityLinker::class)->findOrCreateUser($profile);

    expect($user->getKey())->toBe($existing->getKey());

    expect(SsoIdentity::query()
        ->where('user_id', $existing->getKey())
        ->where('provider', 'microsoft')
        ->exists())->toBeTrue();
});

it('SsoIdentityLinker is idempotent on returning users', function (): void {
    $profile = new SsoUserProfile(
        provider: 'github',
        providerUserId: 'gh_789',
        email: 'returning@test.com',
        accessToken: 'tok_first',
    );

    $user1 = app(SsoIdentityLinker::class)->findOrCreateUser($profile);

    // Second call same provider+pid → same user, NOT a new SsoIdentity row
    $profile2 = new SsoUserProfile(
        provider: 'github',
        providerUserId: 'gh_789',
        email: 'returning@test.com',
        accessToken: 'tok_second',
    );
    $user2 = app(SsoIdentityLinker::class)->findOrCreateUser($profile2);

    expect($user2->getKey())->toBe($user1->getKey());
    expect(SsoIdentity::query()
        ->where('provider', 'github')
        ->where('provider_user_id', 'gh_789')
        ->count())->toBe(1);
});

// ---- HTTP SSO --------------------------------------------------------------

it('GET /api/v1/auth/sso/{provider}/redirect returns authorization URL + state', function (): void {
    $response = $this->getJson('/api/v1/auth/sso/google/redirect');

    $response->assertOk()
        ->assertJsonStructure(['authorization_url', 'state']);

    expect($response->json('authorization_url'))->toContain('https://sso.test/google/authorize')
        ->and(strlen($response->json('state')))->toBe(40);
});

it('POST /api/v1/auth/sso/{provider}/callback exchanges code and returns Sanctum token', function (): void {
    $code = 'sso_test:sub_999:user@gmail.test';
    $response = $this->postJson('/api/v1/auth/sso/google/callback', [
        'code' => $code,
        'redirect_uri' => 'https://app.test/callback',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

    expect($response->json('user.email'))->toBe('user@gmail.test');
    expect(SsoIdentity::query()->where('provider_user_id', 'sub_999')->exists())->toBeTrue();
});

it('POST callback rejects empty code', function (): void {
    $this->postJson('/api/v1/auth/sso/google/callback', ['code' => ''])
        ->assertStatus(422);
});

// ---- Filament admin --------------------------------------------------------

it('admin reaches /admin/teams index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/teams')->assertOk();
});

it('admin reaches /admin/teams/create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/teams/create')->assertOk();
});

it('admin reaches /admin/team-invitations index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/team-invitations')->assertOk();
});

it('admin reaches /admin/sso-identities index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/sso-identities')->assertOk();
});
