<?php

declare(strict_types=1);

use App\Application\Marketing\Services\JsonLdGenerator;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->gen = new JsonLdGenerator;
    $owner = User::factory()->create();
    $this->project = Project::query()->create([
        'slug' => 'jsonld-proj', 'name' => 'JsonLd Test', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);
});

it('emits a valid WebSite schema with SearchAction', function (): void {
    $schema = $this->gen->website($this->project, 'https://example.com');

    expect($schema->type)->toBe('WebSite')
        ->and($schema->data['@context'])->toBe('https://schema.org')
        ->and($schema->data['@type'])->toBe('WebSite')
        ->and($schema->data['name'])->toBe('JsonLd Test')
        ->and($schema->data['potentialAction']['@type'])->toBe('SearchAction')
        ->and($schema->data['potentialAction']['target']['urlTemplate'])->toContain('search?q={search_term_string}');
});

it('emits an Organization schema with logo URL', function (): void {
    $s = $this->gen->organization($this->project, 'https://example.com');
    expect($s->data['@type'])->toBe('Organization')
        ->and($s->data['logo'])->toBe('https://example.com/logo.png');
});

it('emits an Article schema with headline + wordCount + mainEntityOfPage', function (): void {
    $a = Article::query()->create([
        'project_id' => $this->project->id,
        'slug' => 'first-post', 'locale' => 'fr', 'title' => 'First',
        'body' => str_repeat('a ', 200), 'is_pillar' => false,
        'status' => 'published', 'word_count' => 200, 'reading_time_minutes' => 1,
        'quality_score' => 80,
    ]);

    $s = $this->gen->article($a, 'https://example.com');
    expect($s->data['@type'])->toBe('Article')
        ->and($s->data['headline'])->toBe('First')
        ->and($s->data['wordCount'])->toBe(200)
        ->and($s->data['mainEntityOfPage']['@id'])->toBe('https://example.com/first-post');
});

it('combines all FAQs into one FAQPage schema (AEO essential)', function (): void {
    $f1 = Faq::query()->create(['project_id' => $this->project->id, 'locale' => 'fr', 'question' => 'Q1?', 'answer' => 'A1', 'status' => 'published']);
    $f2 = Faq::query()->create(['project_id' => $this->project->id, 'locale' => 'fr', 'question' => 'Q2?', 'answer' => 'A2', 'status' => 'published']);

    $s = $this->gen->faqPage([$f1, $f2]);

    expect($s->data['@type'])->toBe('FAQPage')
        ->and($s->data['mainEntity'])->toHaveCount(2)
        ->and($s->data['mainEntity'][0]['@type'])->toBe('Question')
        ->and($s->data['mainEntity'][0]['name'])->toBe('Q1?');
});

it('emits a BreadcrumbList with positional items', function (): void {
    $s = $this->gen->breadcrumb([
        ['name' => 'Home', 'url' => 'https://example.com/'],
        ['name' => 'Blog', 'url' => 'https://example.com/blog'],
        ['name' => 'Post', 'url' => 'https://example.com/blog/post-1'],
    ]);

    expect($s->data['itemListElement'])->toHaveCount(3)
        ->and($s->data['itemListElement'][0]['position'])->toBe(1)
        ->and($s->data['itemListElement'][2]['name'])->toBe('Post');
});

it('JsonLdSchema toJson() produces valid JSON without escaped slashes', function (): void {
    $s = $this->gen->website($this->project, 'https://example.com/path');
    $json = $s->toJson();
    $decoded = json_decode($json, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($decoded['@type'])->toBe('WebSite')
        ->and($json)->not->toContain('\\/');
});
