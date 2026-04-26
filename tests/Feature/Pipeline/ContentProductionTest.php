<?php

declare(strict_types=1);

use App\Application\Catalog\Services\ContentProductionService;
use App\Application\Shared\Services\AudienceContextService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\ContentProduced;
use App\Domain\Catalog\Events\GitHubRepositoryCreated;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Pipeline\HeuristicContentProductionService;
use App\Infrastructure\Pipeline\Jobs\ProduceContentJob;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function makeContentProject(): EloquentProject
{
    $owner = User::factory()->create();

    return EloquentProject::query()->create([
        'slug' => 'content-1',
        'name' => 'Content Test',
        'description' => 'multilingual content',
        'status' => 'building',
        'locale' => 'fr-FR',
        'owner_id' => $owner->getKey(),
        'metadata' => [
            'blueprint' => [
                'pages' => [
                    ['slug' => 'home', 'title' => 'Home', 'type' => 'landing'],
                    ['slug' => 'pricing', 'title' => 'Pricing', 'type' => 'pricing'],
                ],
                'journeys' => [
                    ['name' => 'Onboarding', 'steps' => ['signup', 'verify', 'use']],
                ],
                'kpis' => [],
            ],
            'target_locales' => ['fr-FR', 'en-US'],
        ],
    ]);
}

// ---- DI binding ------------------------------------------------------------

it('binds ContentProductionService to the heuristic adapter', function (): void {
    expect(app(ContentProductionService::class))->toBeInstanceOf(HeuristicContentProductionService::class);
});

// ---- HeuristicContentProductionService -------------------------------------

it('produces pages, articles and faqs across all locales', function (): void {
    $row = makeContentProject();
    /** @var \App\Domain\Catalog\Entities\Project $project */
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->id);

    $bundle = app(ContentProductionService::class)->produce($project, ['fr-FR', 'en-US']);

    expect($bundle->producedLocales)->toEqualCanonicalizing(['fr-FR', 'en-US'])
        ->and(count($bundle->pageIds))->toBe(4)   // 2 pages × 2 locales
        ->and(count($bundle->articleIds))->toBe(2) // 1 journey × 2 locales
        ->and(count($bundle->faqIds))->toBe(6);    // 3 faqs × 2 locales

    expect(Page::query()->where('project_id', $row->id)->where('locale', 'en-US')->count())->toBe(2)
        ->and(Article::query()->where('project_id', $row->id)->where('locale', 'fr-FR')->where('is_pillar', true)->count())->toBe(1)
        ->and(Faq::query()->where('project_id', $row->id)->where('locale', 'en-US')->count())->toBe(3);
});

it('falls back to a single home page when blueprint has no pages', function (): void {
    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'empty-blueprint',
        'name' => 'Empty BP',
        'status' => 'building',
        'locale' => 'fr-FR',
        'owner_id' => $owner->getKey(),
        'metadata' => ['blueprint' => ['pages' => [], 'journeys' => [], 'kpis' => []]],
    ]);
    /** @var \App\Domain\Catalog\Entities\Project $project */
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->id);

    $bundle = app(ContentProductionService::class)->produce($project, ['fr-FR']);

    expect(count($bundle->pageIds))->toBe(1);
    expect(Page::query()->where('project_id', $row->id)->where('slug', 'home')->exists())->toBeTrue();
});

it('updateOrCreate keeps idempotency on repeated runs', function (): void {
    $row = makeContentProject();
    /** @var \App\Domain\Catalog\Entities\Project $project */
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->id);

    app(ContentProductionService::class)->produce($project, ['fr-FR']);
    app(ContentProductionService::class)->produce($project, ['fr-FR']); // run twice

    expect(Page::query()->where('project_id', $row->id)->count())->toBe(2)
        ->and(Article::query()->where('project_id', $row->id)->count())->toBe(1)
        ->and(Faq::query()->where('project_id', $row->id)->count())->toBe(3);
});

// ---- ProduceContentJob -----------------------------------------------------

it('ProduceContentJob writes content metadata + dispatches ContentProduced', function (): void {
    Event::fake([ContentProduced::class]);

    $row = makeContentProject();
    (new ProduceContentJob((string) $row->id))->handle(
        app(ProjectRepositoryInterface::class),
        app(ContentProductionService::class),
        app(EventDispatcher::class),
        app(AudienceContextService::class),
    );

    $row->refresh();
    expect($row->metadata['content']['pages_count'])->toBe(4)
        ->and($row->metadata['content']['articles_count'])->toBe(2)
        ->and($row->metadata['content']['faqs_count'])->toBe(6);

    Event::assertDispatched(ContentProduced::class, function (ContentProduced $e): bool {
        return $e->pagesCount === 4 && $e->articlesCount === 2 && $e->faqsCount === 6;
    });
});

// ---- Listener chains GitHubRepositoryCreated → ProduceContentJob -----------

it('GitHubRepositoryCreated event queues ProduceContentJob', function (): void {
    Bus::fake([ProduceContentJob::class]);

    event(new GitHubRepositoryCreated('proj-x-1', 'me/proj-x-1', 'https://github.com/me/proj-x-1'));

    Bus::assertDispatched(ProduceContentJob::class, function (ProduceContentJob $j): bool {
        return $j->projectId === 'proj-x-1';
    });
});
