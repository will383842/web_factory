<?php

declare(strict_types=1);

use App\Models\Project as EloquentProject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('rejects unauthenticated requests with 401', function (): void {
    $this->getJson('/api/v1/projects')->assertStatus(401);
});

it('lists projects scoped to the authenticated user', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    EloquentProject::query()->create(['slug' => 'alice-1', 'name' => 'A1', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $alice->getKey(), 'metadata' => []]);
    EloquentProject::query()->create(['slug' => 'bob-1',   'name' => 'B1', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $bob->getKey(),   'metadata' => []]);

    $response = $this->actingAs($alice, 'sanctum')
        ->getJson('/api/v1/projects');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alice-1');
});

it('admins see all projects across all owners', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $other = User::factory()->create();

    EloquentProject::query()->create(['slug' => 'a', 'name' => 'A', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $other->getKey(), 'metadata' => []]);
    EloquentProject::query()->create(['slug' => 'b', 'name' => 'B', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $admin->getKey(), 'metadata' => []]);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/projects');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('creates a new project via POST /api/v1/projects', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects', [
            'slug' => 'api-test',
            'name' => 'Api Test',
            'description' => 'Created via API',
            'locale' => 'fr',
            'primary_domain' => null,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.slug', 'api-test')
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseHas('projects', ['slug' => 'api-test', 'owner_id' => $user->getKey()]);
});

it('rejects invalid slug on store', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects', [
            'slug' => 'NOT a valid slug',
            'name' => 'X',
            'locale' => 'fr',
        ])->assertStatus(422)
        ->assertJsonValidationErrors(['slug']);
});

it('forbids viewing a project owned by someone else (non-admin)', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $row = EloquentProject::query()->create(['slug' => 'bob-only', 'name' => 'B', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $bob->getKey(), 'metadata' => []]);

    $this->actingAs($alice, 'sanctum')
        ->getJson("/api/v1/projects/{$row->getKey()}")
        ->assertStatus(403);
});
