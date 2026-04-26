<?php

declare(strict_types=1);

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('creates a Project, persists it, and dispatches ProjectCreated', function (): void {
    Event::fake([ProjectCreated::class]);

    /** @var User $owner */
    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);

    $project = $handler->handle(new CreateProjectCommand(
        slug: 'awesome-saas',
        name: 'Awesome SaaS',
        description: 'A new platform',
        locale: 'fr',
        primaryDomain: 'awesome.local',
        ownerId: (string) $owner->getKey(),
        metadata: ['stack' => ['framework' => 'laravel']],
    ));

    expect($project->slug->value)->toBe('awesome-saas')
        ->and($project->status->value)->toBe('draft');

    $row = EloquentProject::query()->where('slug', 'awesome-saas')->first();
    expect($row)->not->toBeNull()
        ->and($row->name)->toBe('Awesome SaaS')
        ->and((int) $row->owner_id)->toBe($owner->getKey());

    Event::assertDispatched(ProjectCreated::class, fn (ProjectCreated $e): bool => $e->slug->value === 'awesome-saas'
            && $e->ownerId === (string) $owner->getKey());
});
