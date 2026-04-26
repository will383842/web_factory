<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Test endpoint that returns whatever the middleware tagged.
    Route::middleware(['api'])->get('/test-tenant', function () {
        return response()->json([
            'tenant_project_id' => app()->bound('tenant.project_id') ? app('tenant.project_id') : null,
        ]);
    });
});

it('extracts project_id from the X-Project-Id header', function (): void {
    $response = $this->withHeader('X-Project-Id', '42')
        ->getJson('/test-tenant');

    $response->assertOk()->assertJsonPath('tenant_project_id', '42');
});

it('falls back to the authenticated users first owned project', function (): void {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'owner-proj',
        'name' => 'Owner Proj',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);

    $response = $this->actingAs($owner, 'sanctum')->getJson('/test-tenant');

    $response->assertOk()->assertJsonPath('tenant_project_id', (string) $project->getKey());
});

it('returns null when no clue is available (anonymous request, no header)', function (): void {
    $response = $this->getJson('/test-tenant');
    $response->assertOk()->assertJsonPath('tenant_project_id', null);
});

it('header takes precedence over the user fallback', function (): void {
    $owner = User::factory()->create();
    Project::query()->create([
        'slug' => 'owner-proj',
        'name' => 'Owner Proj',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->withHeader('X-Project-Id', '999')
        ->getJson('/test-tenant');

    $response->assertOk()->assertJsonPath('tenant_project_id', '999');
});
