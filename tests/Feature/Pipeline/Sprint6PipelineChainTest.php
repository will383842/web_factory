<?php

declare(strict_types=1);

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\DTOs\BriefBundle;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Application\Catalog\Services\BriefBuilderService;
use App\Application\Catalog\Services\BriefScorerService;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\BriefBuilt;
use App\Domain\Catalog\Events\BriefScored;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\Events\GitHubRepositoryCreated;
use App\Infrastructure\Pipeline\HeuristicBriefBuilderService;
use App\Infrastructure\Pipeline\HeuristicBriefScorerService;
use App\Infrastructure\Pipeline\Jobs\BuildBriefJob;
use App\Infrastructure\Pipeline\Jobs\InitGitHubRepoJob;
use App\Infrastructure\Pipeline\Jobs\ScoreBriefJob;
use App\Infrastructure\Pipeline\MockGitHubRepositoryService;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('binds the three Sprint-6 service ports to their heuristic adapters', function (): void {
    expect(app(BriefBuilderService::class))->toBeInstanceOf(HeuristicBriefBuilderService::class)
        ->and(app(BriefScorerService::class))->toBeInstanceOf(HeuristicBriefScorerService::class)
        ->and(app(GitHubRepositoryService::class))->toBeInstanceOf(MockGitHubRepositoryService::class);
});

it('builds a brief with at least 35 files via the heuristic builder', function (): void {
    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'brief-1',
        'name' => 'Brief Build Test',
        'description' => 'A clean idea',
        'status' => 'designing',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [
            'analysis' => ['virality_score' => 90, 'value_score' => 70],
            'blueprint' => [
                'pages' => array_map(static fn (int $i) => ['slug' => 'p'.$i, 'title' => 'P'.$i, 'type' => 'static'], range(1, 10)),
                'journeys' => [],
                'kpis' => [],
            ],
            'design' => [
                'tokens' => ['color.primary' => '#000'],
                'mockups_summary' => array_map(static fn (int $i) => 'm'.$i, range(1, 8)),
            ],
        ],
    ]);

    $project = app(ProjectRepositoryInterface::class)
        ->findById((string) $row->getKey());
    assert($project !== null);

    $bundle = (new HeuristicBriefBuilderService)->build($project);

    expect($bundle->fileCount())->toBeGreaterThanOrEqual(35)
        ->and($bundle->files)->toHaveKey('README.md')
        ->and($bundle->files)->toHaveKey('blueprint.json')
        ->and($bundle->files)->toHaveKey('design/tokens.json')
        ->and($bundle->checksum)->toHaveLength(64); // sha256
});

it('scorer accepts a complete brief (>= 85) and rejects a sparse one', function (): void {
    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'scorer-1',
        'name' => 'Scorer Test',
        'description' => 'A clean idea',
        'status' => 'designing',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [
            'analysis' => ['virality_score' => 90, 'value_score' => 70],
            'blueprint' => [
                'pages' => array_map(static fn (int $i) => ['slug' => 'p'.$i, 'title' => 'P'.$i, 'type' => 'static'], range(1, 10)),
                'journeys' => [],
                'kpis' => [],
            ],
            'design' => [
                'tokens' => ['color.primary' => '#000'],
                'mockups_summary' => array_map(static fn (int $i) => 'm'.$i, range(1, 8)),
            ],
        ],
    ]);
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->getKey());
    assert($project !== null);
    $project->score(90, 70);

    $full = (new HeuristicBriefBuilderService)->build($project);
    $sparse = new BriefBundle(files: ['README.md' => 'short'], checksum: 'x');

    $scorer = new HeuristicBriefScorerService;

    expect($scorer->score($project, $full)->passes())->toBeTrue()
        ->and($scorer->score($project, $sparse)->passes())->toBeFalse();
});

it('mock GitHub adapter returns the expected coordinates for the project slug', function (): void {
    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'gh-co',
        'name' => 'GH Coords',
        'status' => 'building',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->getKey());
    assert($project !== null);

    $repo = (new MockGitHubRepositoryService)->createRepository($project);

    expect($repo->fullName)->toBe('webfactory-org/gh-co')
        ->and($repo->htmlUrl)->toBe('https://github.com/webfactory-org/gh-co')
        ->and($repo->sshUrl)->toBe('git@github.com:webfactory-org/gh-co.git')
        ->and($repo->defaultBranch)->toBe('main');
});

it('runs the full 7-step pipeline synchronously: status=deployed + metadata enriched', function (): void {
    config(['queue.default' => 'sync']);
    Storage::fake('s3');

    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);
    $project = $handler->handle(new CreateProjectCommand(
        slug: 'full-pipeline',
        name: 'Full Pipeline',
        description: str_repeat('a no-code AI tool ', 12),
        locale: 'en-US',
        primaryDomain: 'fullpipeline.local',
        ownerId: (string) $owner->getKey(),
        metadata: [],
    ));

    $row = EloquentProject::query()->find($project->id);

    // Sprint 15 + 16 chain steps 6 (content) + 7 (deploy) automatically.
    expect($row->status)->toBe('deployed');

    $meta = (array) $row->metadata;
    expect($meta)->toHaveKey('analysis')
        ->and($meta)->toHaveKey('blueprint')
        ->and($meta)->toHaveKey('design')
        ->and($meta)->toHaveKey('brief')
        ->and($meta)->toHaveKey('brief_score')
        ->and($meta)->toHaveKey('github')
        ->and($meta)->toHaveKey('content')
        ->and($meta)->toHaveKey('deployment');

    expect($meta['brief_score']['passes'])->toBeTrue()
        ->and($meta['github']['full_name'])->toBe('webfactory-org/full-pipeline')
        ->and($meta['deployment']['provider'])->toBe('placeholder')
        ->and($meta['deployment']['live_url'])->toBe('https://full-pipeline.webfactory.test');
});

it('queues BuildBriefJob when DesignGenerated fires', function (): void {
    Bus::fake([BuildBriefJob::class, ScoreBriefJob::class, InitGitHubRepoJob::class]);

    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'evt-design',
        'name' => 'Evt Design',
        'status' => 'designing',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => ['analysis' => [], 'blueprint' => [], 'design' => []],
    ]);

    Event::dispatch(new DesignGenerated((string) $row->getKey(), 8));

    Bus::assertDispatched(BuildBriefJob::class);
});

it('dispatches BriefBuilt + BriefScored + GitHubRepositoryCreated through the chain', function (): void {
    config(['queue.default' => 'sync']);
    Storage::fake('s3');
    Event::fake([BriefBuilt::class, BriefScored::class, GitHubRepositoryCreated::class]);

    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);
    $handler->handle(new CreateProjectCommand(
        slug: 'evt-chain',
        name: 'Evt Chain',
        description: str_repeat('a no-code AI tool ', 12),
        locale: 'en-US',
        primaryDomain: 'evtchain.local',
        ownerId: (string) $owner->getKey(),
        metadata: [],
    ));

    Event::assertDispatched(BriefBuilt::class);
    Event::assertDispatched(BriefScored::class);
    Event::assertDispatched(GitHubRepositoryCreated::class);
});
