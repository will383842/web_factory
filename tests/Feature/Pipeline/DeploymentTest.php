<?php

declare(strict_types=1);

use App\Application\Catalog\Services\DeploymentService;
use App\Application\Marketing\Services\IndexNowPingService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\ContentProduced;
use App\Domain\Catalog\Events\ProjectDeployed;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Pipeline\Jobs\DeployProjectJob;
use App\Infrastructure\Pipeline\PlaceholderDeploymentService;
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

function makeDeployProject(): EloquentProject
{
    $owner = User::factory()->create();

    return EloquentProject::query()->create([
        'slug' => 'deploy-1',
        'name' => 'Deploy Test',
        'status' => ProjectStatus::Building->value,
        'locale' => 'fr-FR',
        'owner_id' => $owner->getKey(),
        'metadata' => [],
    ]);
}

it('binds DeploymentService to the placeholder', function (): void {
    expect(app(DeploymentService::class))->toBeInstanceOf(PlaceholderDeploymentService::class);
    expect(app(DeploymentService::class)->provider())->toBe('placeholder');
});

it('PlaceholderDeploymentService returns synthetic live URL with the project slug', function (): void {
    $row = makeDeployProject();
    /** @var Project $project */
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->id);

    $result = app(DeploymentService::class)->deploy($project);

    expect($result->success)->toBeTrue()
        ->and($result->liveUrl)->toBe('https://deploy-1.webfactory.test')
        ->and($result->deploymentId)->toStartWith('dep_');
});

it('DeployProjectJob writes deployment metadata + status=deployed + dispatches ProjectDeployed', function (): void {
    Event::fake([ProjectDeployed::class]);

    $row = makeDeployProject();

    (new DeployProjectJob((string) $row->id))->handle(
        app(ProjectRepositoryInterface::class),
        app(DeploymentService::class),
        app(EventDispatcher::class),
        app(IndexNowPingService::class),
    );

    $row->refresh();
    expect($row->status)->toBe(ProjectStatus::Deployed->value)
        ->and($row->metadata['deployment']['live_url'])->toBe('https://deploy-1.webfactory.test')
        ->and($row->metadata['deployment']['provider'])->toBe('placeholder');

    Event::assertDispatched(ProjectDeployed::class, function (ProjectDeployed $e): bool {
        return str_contains($e->liveUrl, 'webfactory.test') && $e->provider === 'placeholder';
    });
});

it('ContentProduced event queues DeployProjectJob', function (): void {
    Bus::fake([DeployProjectJob::class]);

    event(new ContentProduced(
        projectId: 'proj-deploy-x',
        pagesCount: 4,
        articlesCount: 2,
        faqsCount: 6,
        producedLocales: ['fr-FR', 'en-US'],
    ));

    Bus::assertDispatched(DeployProjectJob::class, function (DeployProjectJob $j): bool {
        return $j->projectId === 'proj-deploy-x';
    });
});
