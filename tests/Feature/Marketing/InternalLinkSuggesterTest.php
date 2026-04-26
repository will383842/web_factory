<?php

declare(strict_types=1);

use App\Application\Marketing\Services\InternalLinkSuggester;
use App\Infrastructure\Content\PgVectorKnowledgeBase;
use App\Models\Article;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses pgvector KB to suggest related articles, excluding the source itself', function (): void {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'slug' => 'links-proj', 'name' => 'Links', 'status' => 'draft',
        'locale' => 'fr', 'owner_id' => $owner->getKey(), 'metadata' => [],
    ]);

    /** @var PgVectorKnowledgeBase $kb */
    $kb = app(PgVectorKnowledgeBase::class);

    $a1 = Article::query()->create([
        'project_id' => $project->id, 'slug' => 'art-1', 'locale' => 'fr',
        'title' => 'Comment apprendre python rapidement',
        'body' => 'tutoriel python pour debutants apprentissage rapide', 'is_pillar' => false,
        'status' => 'published', 'word_count' => 8, 'reading_time_minutes' => 1, 'quality_score' => 80,
    ]);
    $a2 = Article::query()->create([
        'project_id' => $project->id, 'slug' => 'art-2', 'locale' => 'fr',
        'title' => 'Astuces python avancees',
        'body' => 'python avance fonctions decorateurs metaclasses', 'is_pillar' => false,
        'status' => 'published', 'word_count' => 6, 'reading_time_minutes' => 1, 'quality_score' => 80,
    ]);
    $a3 = Article::query()->create([
        'project_id' => $project->id, 'slug' => 'art-3', 'locale' => 'fr',
        'title' => 'Recettes de cuisine italienne',
        'body' => 'pasta carbonara tomates basilic mozzarella', 'is_pillar' => false,
        'status' => 'published', 'word_count' => 5, 'reading_time_minutes' => 1, 'quality_score' => 80,
    ]);

    // Ingest each article body via the KB (mirror what the listener does)
    $kb->ingest((string) $project->id, 'article', (int) $a1->getKey(), 'fr', $a1->body);
    $kb->ingest((string) $project->id, 'article', (int) $a2->getKey(), 'fr', $a2->body);
    $kb->ingest((string) $project->id, 'article', (int) $a3->getKey(), 'fr', $a3->body);

    $suggestions = (new InternalLinkSuggester($kb))->suggestForArticle($a1, 5);

    // a1 should be excluded; a2 (python) more similar than a3 (cuisine)
    $sourceIds = array_column(array_map(static fn ($s) => ['id' => $s->sourceId], $suggestions), 'id');
    expect($sourceIds)->not->toContain((int) $a1->getKey())
        ->and($suggestions)->not->toBeEmpty();
});
