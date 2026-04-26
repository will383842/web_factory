<?php

declare(strict_types=1);

use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var User $owner */
    $owner = User::factory()->create();
    $this->ownerId = (string) $owner->getKey();
    $this->repo = app(ProjectRepositoryInterface::class);
});

it('returns null for an unknown project id', function (): void {
    expect($this->repo->findById('999999'))->toBeNull();
});

it('persists a domain Project and rehydrates it identically', function (): void {
    $domain = Project::submit(
        id: '0', // placeholder, ignored by save() since we rehydrate from DB
        slug: new Slug('my-saas'),
        name: 'My SaaS',
        description: 'A test project',
        locale: new Locale('en-US'),
        primaryDomain: 'example.com',
        ownerId: $this->ownerId,
        metadata: ['stack' => ['framework' => 'laravel']],
    );

    // Persist via Eloquent first to obtain an id (mirrors handler flow)
    $row = EloquentProject::query()->create([
        'slug' => $domain->slug->value,
        'name' => $domain->name,
        'description' => $domain->description,
        'status' => $domain->status->value,
        'locale' => $domain->locale->value,
        'primary_domain' => $domain->primaryDomain,
        'virality_score' => 0,
        'value_score' => 0,
        'owner_id' => (int) $this->ownerId,
        'metadata' => $domain->metadata,
    ]);

    $reloaded = $this->repo->findById((string) $row->getKey());

    expect($reloaded)->toBeInstanceOf(Project::class)
        ->and($reloaded->slug->value)->toBe('my-saas')
        ->and($reloaded->name)->toBe('My SaaS')
        ->and($reloaded->status)->toBe(ProjectStatus::Draft)
        ->and($reloaded->locale->value)->toBe('en-US')
        ->and($reloaded->primaryDomain)->toBe('example.com');
});

it('finds a project by slug', function (): void {
    EloquentProject::query()->create([
        'slug' => 'lookup-me',
        'name' => 'Lookup',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => (int) $this->ownerId,
        'metadata' => [],
    ]);

    $found = $this->repo->findBySlug(new Slug('lookup-me'));

    expect($found)->not->toBeNull()
        ->and($found->slug->value)->toBe('lookup-me');
});

it('lists projects by owner ordered by id desc', function (): void {
    EloquentProject::query()->create(['slug' => 'a', 'name' => 'A', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => (int) $this->ownerId, 'metadata' => []]);
    EloquentProject::query()->create(['slug' => 'b', 'name' => 'B', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => (int) $this->ownerId, 'metadata' => []]);

    $list = $this->repo->findByOwner($this->ownerId);

    expect($list)->toHaveCount(2)
        ->and($list[0]->slug->value)->toBe('b'); // newest first
});

it('lists projects by status', function (): void {
    EloquentProject::query()->create(['slug' => 'd1', 'name' => 'D1', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => (int) $this->ownerId, 'metadata' => []]);
    EloquentProject::query()->create(['slug' => 'a1', 'name' => 'A1', 'status' => 'analyzing', 'locale' => 'fr', 'owner_id' => (int) $this->ownerId, 'metadata' => []]);

    $analyzing = $this->repo->findByStatus(ProjectStatus::Analyzing);

    expect($analyzing)->toHaveCount(1)
        ->and($analyzing[0]->slug->value)->toBe('a1');
});
