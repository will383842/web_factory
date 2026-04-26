<?php

declare(strict_types=1);

use App\Application\Marketing\Services\SeoHubAggregator;
use App\Models\Article;
use App\Models\Faq;
use App\Models\News;
use App\Models\Project;
use App\Models\User;
use App\Settings\AppearanceSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

// ---- News Resource ---------------------------------------------------------

it('admin reaches /admin/news index', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/news')->assertOk();
});

it('admin reaches /admin/news/create form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/news/create')->assertOk();
});

it('isExpired returns true for past expires_at', function (): void {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'expired', 'name' => 'X', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);
    $past = News::query()->create([
        'project_id' => $project->id, 'slug' => 'old', 'locale' => 'fr',
        'title' => 'Old', 'body' => 'b', 'status' => 'published',
        'expires_at' => now()->subDay(),
    ]);
    expect($past->isExpired())->toBeTrue();
});

// ---- Appearance settings ---------------------------------------------------

it('AppearanceSettings ships default Sprint-11 palette + typography + radii', function (): void {
    $s = app(AppearanceSettings::class);
    expect($s->colorPrimary)->toBe('#4F46E5')
        ->and($s->fontHeading)->toContain('Inter')
        ->and($s->radiusMd)->toBe('0.5rem')
        ->and($s->spacingUnit)->toBe('0.25rem');
});

it('Appearance settings can be saved + reloaded', function (): void {
    $s = app(AppearanceSettings::class);
    $s->colorPrimary = '#FF0000';
    $s->save();
    app()->forgetInstance(AppearanceSettings::class);
    expect(app(AppearanceSettings::class)->colorPrimary)->toBe('#FF0000');
});

it('admin reaches /admin/manage-appearance-settings', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/manage-appearance-settings')->assertOk();
});

// ---- SEO Hub ---------------------------------------------------------------

it('SeoHubAggregator counts content per project', function (): void {
    $owner = User::factory()->create();
    $a = Project::query()->create(['slug' => 'pa', 'name' => 'A', 'status' => 'draft', 'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => []]);

    Article::query()->create(['project_id' => $a->id, 'slug' => 's1', 'locale' => 'fr', 'title' => 'T1', 'body' => 'a', 'is_pillar' => true, 'status' => 'published', 'word_count' => 100, 'reading_time_minutes' => 1, 'quality_score' => 85]);
    Faq::query()->create(['project_id' => $a->id, 'locale' => 'fr', 'question' => 'q?', 'answer' => 'a', 'status' => 'published', 'is_featured' => true]);

    $summary = app(SeoHubAggregator::class)->summary((int) $a->id);

    expect($summary['articles']['total'])->toBe(1)
        ->and($summary['articles']['pillar'])->toBe(1)
        ->and($summary['articles']['avg_quality'])->toBe(85)
        ->and($summary['faqs']['featured'])->toBe(1);
});

it('admin reaches /admin/seo-hub', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/admin/seo-hub')->assertOk();
});
