<?php

declare(strict_types=1);

use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Catalog\Events\ProjectStatusChanged;
use App\Domain\Catalog\Exceptions\InvalidProjectStatusTransitionException;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

function makeProject(string $id = 'p-1'): Project
{
    return Project::submit(
        id: $id,
        slug: new Slug('hello-project'),
        name: 'Hello',
        description: null,
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: 'u-1',
    );
}

it('starts in draft and records ProjectCreated', function (): void {
    $p = makeProject();

    expect($p->status)->toBe(ProjectStatus::Draft)
        ->and($p->viralityScore)->toBe(0)
        ->and($p->valueScore)->toBe(0)
        ->and($p->pendingEvents()[0])->toBeInstanceOf(ProjectCreated::class);
});

it('transitions forward through the 5-step pipeline', function (): void {
    $p = makeProject();
    $p->flushEvents(); // discard ProjectCreated

    $p->transitionTo(ProjectStatus::Analyzing);
    $p->transitionTo(ProjectStatus::Blueprinting);
    $p->transitionTo(ProjectStatus::Designing);
    $p->transitionTo(ProjectStatus::Building);
    $p->transitionTo(ProjectStatus::Deployed);

    expect($p->status)->toBe(ProjectStatus::Deployed)
        ->and($p->pendingEvents())->toHaveCount(5)
        ->and($p->pendingEvents()[0])->toBeInstanceOf(ProjectStatusChanged::class);
});

it('refuses to skip status steps', function (): void {
    makeProject()->transitionTo(ProjectStatus::Designing);
})->throws(InvalidProjectStatusTransitionException::class);

it('refuses to go backwards', function (): void {
    $p = makeProject();
    $p->transitionTo(ProjectStatus::Analyzing);
    $p->transitionTo(ProjectStatus::Draft);
})->throws(InvalidProjectStatusTransitionException::class);

it('can archive from any non-terminal state', function (): void {
    $p = makeProject();
    $p->transitionTo(ProjectStatus::Analyzing);
    $p->archive();

    expect($p->status)->toBe(ProjectStatus::Archived);
});

it('clamps virality + value scores to 0-100', function (): void {
    $p = makeProject();
    $p->score(150, -10);
    expect($p->viralityScore)->toBe(100)
        ->and($p->valueScore)->toBe(0);
});

it('does NOT record events on rehydration', function (): void {
    $p = Project::rehydrate(
        id: 'p-x',
        slug: new Slug('rehydrated'),
        name: 'X',
        description: null,
        status: ProjectStatus::Building,
        locale: new Locale('en'),
        primaryDomain: null,
        viralityScore: 80,
        valueScore: 60,
        ownerId: 'u-1',
        metadata: [],
    );

    expect($p->pendingEvents())->toBeEmpty()
        ->and($p->status)->toBe(ProjectStatus::Building);
});
