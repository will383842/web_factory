<?php

declare(strict_types=1);

use App\Infrastructure\Content\HeuristicEmbeddingService;

it('produces a 384-dim vector', function (): void {
    $svc = new HeuristicEmbeddingService;
    $v = $svc->embed('hello world');
    expect($v)->toHaveCount(384)
        ->and($svc->dimensions())->toBe(384);
});

it('produces L2-normalized vectors (||v||=1)', function (): void {
    $v = (new HeuristicEmbeddingService)->embed('a quick brown fox jumps over the lazy dog');
    $magnitude = sqrt(array_sum(array_map(static fn (float $x): float => $x * $x, $v)));
    expect($magnitude)->toBeGreaterThan(0.99)->toBeLessThan(1.01);
});

it('produces non-zero embedding even for empty input', function (): void {
    $v = (new HeuristicEmbeddingService)->embed('');
    expect(array_sum(array_map('abs', $v)))->toBeGreaterThan(0.0);
});

it('two semantically similar strings have higher dot product than two unrelated', function (): void {
    $svc = new HeuristicEmbeddingService;
    $a = $svc->embed('the sky is blue today');
    $b = $svc->embed('today the sky was very blue');
    $c = $svc->embed('quarterly tax filings deadline reminder');

    $dot = static fn (array $x, array $y): float => array_sum(array_map(static fn ($a, $b) => $a * $b, $x, $y));

    expect($dot($a, $b))->toBeGreaterThan($dot($a, $c));
});
