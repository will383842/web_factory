<?php

declare(strict_types=1);

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Domain\Catalog\Events\BlueprintGenerated;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\Events\IdeaAnalyzed;
use App\Infrastructure\Pipeline\Jobs\AnalyzeProjectIdeaJob;
use App\Infrastructure\Pipeline\Jobs\GenerateBlueprintJob;
use App\Infrastructure\Pipeline\Jobs\GenerateDesignJob;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('queues AnalyzeProjectIdeaJob right after project creation', function (): void {
    Bus::fake();
    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);
    $handler->handle(new CreateProjectCommand(
        slug: 'queued-1',
        name: 'Queued',
        description: 'desc',
        locale: 'fr',
        primaryDomain: null,
        ownerId: (string) $owner->getKey(),
        metadata: [],
    ));

    Bus::assertDispatched(AnalyzeProjectIdeaJob::class);
});

it('runs steps 1-3 synchronously and produces analysis/blueprint/design metadata', function (): void {
    // Sprint-5 scope test: only verify that steps 1-3 produce their metadata
    // payloads. Steps 4-5 (Sprint 6) chain right after; the full S0->Building
    // assertion lives in Sprint6PipelineChainTest.
    config(['queue.default' => 'sync']);
    Storage::fake('s3');

    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);
    $domainProject = $handler->handle(new CreateProjectCommand(
        slug: 'sync-pipeline',
        name: 'Sync Pipeline',
        description: str_repeat('a no-code AI tool ', 10),
        locale: 'en-US',
        primaryDomain: 'sync.local',
        ownerId: (string) $owner->getKey(),
        metadata: [],
    ));

    $row = EloquentProject::query()->find($domainProject->id);

    expect($row->virality_score)->toBeGreaterThan(0)
        ->and($row->value_score)->toBeGreaterThan(0);

    $meta = (array) $row->metadata;
    expect($meta)->toHaveKey('analysis')
        ->and($meta)->toHaveKey('blueprint')
        ->and($meta)->toHaveKey('design')
        ->and($meta['blueprint']['pages'])->toHaveCount(10)
        ->and($meta['design']['mockups_count'])->toBe(8);
});

it('chains GenerateBlueprintJob from AnalyzeProjectIdeaJob', function (): void {
    Bus::fake([GenerateBlueprintJob::class, GenerateDesignJob::class]);

    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'chain-1',
        'name' => 'Chain',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);

    AnalyzeProjectIdeaJob::dispatchSync((string) $row->getKey());

    Bus::assertDispatched(GenerateBlueprintJob::class);
});

it('dispatches the IdeaAnalyzed domain event when step 1 finishes', function (): void {
    config(['queue.default' => 'sync']);
    Event::fake([IdeaAnalyzed::class, BlueprintGenerated::class, DesignGenerated::class]);

    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'evt-1',
        'name' => 'Evt',
        'status' => 'draft',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);

    AnalyzeProjectIdeaJob::dispatchSync((string) $row->getKey());

    Event::assertDispatched(IdeaAnalyzed::class);
});
