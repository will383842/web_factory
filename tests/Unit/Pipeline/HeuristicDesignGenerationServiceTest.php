<?php

declare(strict_types=1);

use App\Application\Catalog\DTOs\Blueprint;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Infrastructure\Pipeline\HeuristicDesignGenerationService;

it('emits the canonical token set and 8 mockups', function (): void {
    $project = Project::submit(
        id: '1',
        slug: new Slug('demo'),
        name: 'Demo Co.',
        description: null,
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: '1',
    );
    $blueprint = new Blueprint(pages: [], journeys: [], kpis: []);

    $design = (new HeuristicDesignGenerationService)->generate($project, $blueprint);

    expect($design->mockups)->toHaveCount(8)
        ->and($design->tokens)->toHaveKey('color.primary')
        ->and($design->tokens)->toHaveKey('font.heading')
        ->and($design->tokens)->toHaveKey('spacing.unit');
});

it('embeds the project slug + name in each mockup HTML', function (): void {
    $project = Project::submit(
        id: '1',
        slug: new Slug('uniqueid'),
        name: 'TheName',
        description: null,
        locale: new Locale('fr'),
        primaryDomain: null,
        ownerId: '1',
    );
    $blueprint = new Blueprint(pages: [], journeys: [], kpis: []);

    $design = (new HeuristicDesignGenerationService)->generate($project, $blueprint);

    foreach ($design->mockups as $m) {
        expect($m['html'])->toContain('uniqueid')
            ->and($m['html'])->toContain('TheName');
    }
});
