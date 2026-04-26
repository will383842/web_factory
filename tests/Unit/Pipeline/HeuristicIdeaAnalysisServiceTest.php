<?php

declare(strict_types=1);

use App\Domain\Catalog\Entities\Project;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Infrastructure\Pipeline\HeuristicIdeaAnalysisService;

function p(string $description = '', ?string $domain = null, string $locale = 'fr'): Project
{
    return Project::submit(
        id: '1',
        slug: new Slug('test'),
        name: 'Test Project Name',
        description: $description,
        locale: new Locale($locale),
        primaryDomain: $domain,
        ownerId: '1',
    );
}

it('produces a 0-100 clamped score and returns a result DTO', function (): void {
    $r = (new HeuristicIdeaAnalysisService)->analyze(p('A standard description'));
    expect($r->viralityScore)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
        ->and($r->valueScore)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
});

it('rewards primary domain and longer description on the value score', function (): void {
    $low = (new HeuristicIdeaAnalysisService)->analyze(p('short'));
    $high = (new HeuristicIdeaAnalysisService)
        ->analyze(p(str_repeat('detailed positioning ', 30), 'example.com'));

    expect($high->valueScore)->toBeGreaterThan($low->valueScore);
});

it('flags missing domain and short descriptions as clarifications', function (): void {
    $r = (new HeuristicIdeaAnalysisService)->analyze(p('hi'));

    expect($r->clarifications)->not->toBeEmpty();
});

it('boosts virality on AI / collab / no-code keywords', function (): void {
    $plain = (new HeuristicIdeaAnalysisService)->analyze(p('a normal cookie shop'));
    $viral = (new HeuristicIdeaAnalysisService)->analyze(p('a no-code AI tool for collab teams'));

    expect($viral->viralityScore)->toBeGreaterThan($plain->viralityScore);
});
