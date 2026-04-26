<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\InvalidSlugException;
use App\Domain\Shared\ValueObjects\Slug;

it('accepts a kebab-case ASCII slug', function (): void {
    $slug = new Slug('hello-world-42');
    expect((string) $slug)->toBe('hello-world-42');
});

it('rejects unicode characters', function (): void {
    new Slug('café-au-lait');
})->throws(InvalidSlugException::class);

it('rejects leading or trailing dashes', function (): void {
    new Slug('-foo');
})->throws(InvalidSlugException::class);

it('rejects upper case', function (): void {
    new Slug('Hello-World');
})->throws(InvalidSlugException::class);
