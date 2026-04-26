<?php

declare(strict_types=1);

it('exposes the Laravel health endpoint at /up', function (): void {
    $response = $this->get('/up');

    $response->assertOk();
});

it('boots the application without errors', function (): void {
    expect(app())
        ->not->toBeNull()
        ->and(app()->version())->toMatch('/^13\./');
});

it('has the expected DDD bounded contexts directories', function (): void {
    $boundedContexts = [
        'Identity', 'Catalog', 'Content', 'Marketing', 'Billing',
        'Communication', 'Search', 'Analytics', 'Ai', 'Compliance', 'Shared',
    ];

    foreach ($boundedContexts as $bc) {
        expect(is_dir(app_path("Domain/{$bc}")))->toBeTrue("Domain/{$bc} directory missing");
    }
});
