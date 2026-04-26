<?php

declare(strict_types=1);

use App\Application\Marketing\Services\AeoOptimizer;

it('scores a fully AEO-optimized article high (≥ 80)', function (): void {
    $body = <<<'MD'
        ## TL;DR

        This is a short summary.

        ## How does it work?

        It works by combining steps. Quick answer below.

        ## Why is this useful?

        It is a useful tool for many tasks.

        - First reason
        - Second reason
        - Third reason

        ## FAQ

        Common questions answered.
        MD;

    $r = (new AeoOptimizer)->evaluate($body);
    expect($r['score'])->toBeGreaterThanOrEqual(80);
});

it('flags a wall-of-text article with low score and concrete suggestions', function (): void {
    $body = str_repeat('Just a long paragraph with no headings no FAQ no tldr no list. ', 30);

    $r = (new AeoOptimizer)->evaluate($body);
    expect($r['score'])->toBeLessThan(40)
        ->and($r['suggestions'])->not->toBeEmpty();
});

it('detects a Q-style heading and gives partial credit', function (): void {
    $r = (new AeoOptimizer)->evaluate("## How does X work?\n\nShort answer here.");
    expect($r['score'])->toBeGreaterThan(0);
});
