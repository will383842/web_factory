<?php

declare(strict_types=1);

use App\Domain\Catalog\Entities\Project;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Infrastructure\Pipeline\HeuristicBlueprintGenerationService;

it('emits 10 standard pages, 3 journeys, and 5 KPIs', function (): void {
    $project = Project::submit(
        id: '1',
        slug: new Slug('demo'),
        name: 'Demo',
        description: null,
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: '1',
    );

    $blueprint = (new HeuristicBlueprintGenerationService)->generate($project);

    expect($blueprint->pages)->toHaveCount(10)
        ->and($blueprint->journeys)->toHaveCount(3)
        ->and($blueprint->kpis)->toHaveCount(5);
});

it('always includes the home + legal pages', function (): void {
    $project = Project::submit(
        id: '1',
        slug: new Slug('x'),
        name: 'X',
        description: null,
        locale: new Locale('en'),
        primaryDomain: null,
        ownerId: '1',
    );
    $blueprint = (new HeuristicBlueprintGenerationService)->generate($project);

    $slugs = array_column($blueprint->pages, 'slug');
    expect($slugs)->toContain('home', 'legal/terms', 'legal/privacy', 'legal/cookies');
});
