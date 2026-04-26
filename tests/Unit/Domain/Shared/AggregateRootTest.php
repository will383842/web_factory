<?php

declare(strict_types=1);

use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

it('records a domain event on factory submit()', function (): void {
    $project = Project::submit(
        id: 'proj-1',
        slug: new Slug('hello-project'),
        name: 'Hello',
        description: 'A test project',
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: 'user-1',
    );

    $events = $project->pendingEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ProjectCreated::class)
        ->and($events[0]->aggregateId())->toBe('proj-1')
        ->and($events[0]->eventName())->toBe('catalog.project.created');
});

it('does NOT record events on rehydration', function (): void {
    $project = Project::rehydrate(
        id: 'proj-2',
        slug: new Slug('rehydrated'),
        name: 'Rehydrated',
        description: null,
        status: ProjectStatus::Draft,
        locale: new Locale('en'),
        primaryDomain: null,
        viralityScore: 0,
        valueScore: 0,
        ownerId: 'user-1',
        metadata: [],
    );

    expect($project->pendingEvents())->toBeEmpty();
});

it('flushEvents pops and clears recorded events', function (): void {
    $project = Project::submit(
        id: 'proj-3',
        slug: new Slug('one'),
        name: 'Three',
        description: null,
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: 'user-1',
    );

    $first = $project->flushEvents();
    $second = $project->flushEvents();

    expect($first)->toHaveCount(1)
        ->and($second)->toBeEmpty();
});
