<?php

declare(strict_types=1);

use App\Domain\Content\Entities\Article;
use App\Domain\Content\ValueObjects\ContentStatus;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Infrastructure\Content\PgVectorKnowledgeBase;
use App\Models\Article as EloquentArticle;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeKbProject(string $slug = 'kb-proj'): EloquentProject
{
    $owner = User::factory()->create();

    return EloquentProject::query()->create([
        'slug' => $slug,
        'name' => 'KB Test Project',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);
}

it('ingests a chunk and stores its 384-dim embedding via pgvector', function (): void {
    $project = makeKbProject('kb-ingest');
    $kb = app(PgVectorKnowledgeBase::class);

    $result = $kb->ingest((string) $project->id, 'manual', null, 'fr', 'Hello world from the KB');

    expect($result['embedding'])->toHaveCount(384);
    $this->assertDatabaseHas('knowledge_chunks', [
        'project_id' => $project->id,
        'source_type' => 'manual',
        'content' => 'Hello world from the KB',
    ]);
});

it('returns the most semantically similar chunk first', function (): void {
    $project = makeKbProject('kb-search');
    $kb = app(PgVectorKnowledgeBase::class);

    $kb->ingest((string) $project->id, 'manual', null, 'fr', 'Le ciel est bleu et la mer est belle aujourd hui');
    $kb->ingest((string) $project->id, 'manual', null, 'fr', 'Je mange des pommes et des bananes au petit dejeuner');
    $kb->ingest((string) $project->id, 'manual', null, 'fr', 'Le ciel etait gris ce matin sur Paris');

    $results = $kb->search((string) $project->id, 'ciel meteo Paris', 3);

    expect($results)->toHaveCount(3)
        ->and($results[0]->content)->toContain('Paris')
        ->and($results[0]->similarity)->toBeGreaterThan($results[2]->similarity);
});

it('isolates chunks by project_id (no cross-tenant leak)', function (): void {
    $a = makeKbProject('tenant-a');
    $b = makeKbProject('tenant-b');
    $kb = app(PgVectorKnowledgeBase::class);

    $kb->ingest((string) $a->id, 'manual', null, 'fr', 'Tenant A secret data');
    $kb->ingest((string) $b->id, 'manual', null, 'fr', 'Tenant B secret data');

    $resultsForA = $kb->search((string) $a->id, 'secret data', 5);

    expect($resultsForA)->toHaveCount(1)
        ->and($resultsForA[0]->content)->toContain('Tenant A');
});

it('auto-ingests an article body into the KB on ArticlePublished event', function (): void {
    $project = makeKbProject('auto-ingest');

    /** @var EloquentArticle $row */
    $row = EloquentArticle::query()->create([
        'project_id' => $project->id,
        'slug' => 'first-post',
        'locale' => 'fr',
        'title' => 'First Post',
        'body' => 'This is the body of the first article — full of unique tokens like XYZ123ABC.',
        'is_pillar' => false,
        'status' => 'draft',
        'word_count' => 16,
        'reading_time_minutes' => 1,
        'quality_score' => 80,
    ]);

    // Build the domain aggregate, publish, dispatch.
    $article = Article::rehydrate(
        id: (string) $row->getKey(),
        projectId: (string) $project->id,
        slug: new Slug('first-post'),
        locale: new Locale('fr'),
        title: 'First Post',
        excerpt: null,
        body: $row->body,
        featuredImageUrl: null,
        seoKeywords: [],
        isPillar: false,
        status: ContentStatus::Draft,
        wordCount: 16,
        readingTimeMinutes: 1,
        qualityScore: 80,
    );
    $article->publish();
    foreach ($article->flushEvents() as $event) {
        event($event);
    }

    $this->assertDatabaseHas('knowledge_chunks', [
        'project_id' => $project->id,
        'source_type' => 'article',
        'source_id' => $row->getKey(),
    ]);
});
