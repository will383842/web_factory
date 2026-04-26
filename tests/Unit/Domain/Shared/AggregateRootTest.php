<?php

declare(strict_types=1);

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Events\ProductCreated;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Slug;

it('records a domain event on factory creation', function (): void {
    $product = Product::create(
        id: 'prod-1',
        slug: new Slug('hello-product'),
        name: 'Hello',
        price: Money::fromMinor(1000, 'EUR'),
    );

    $events = $product->pendingEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ProductCreated::class)
        ->and($events[0]->aggregateId())->toBe('prod-1')
        ->and($events[0]->eventName())->toBe('catalog.product.created');
});

it('does NOT record events on rehydration', function (): void {
    $product = Product::rehydrate(
        id: 'prod-2',
        slug: new Slug('rehydrated'),
        name: 'Rehydrated',
        price: Money::fromMinor(500, 'USD'),
    );

    expect($product->pendingEvents())->toBeEmpty();
});

it('flushEvents pops and clears recorded events', function (): void {
    $product = Product::create(
        id: 'prod-3',
        slug: new Slug('one'),
        name: 'Three',
        price: Money::fromMinor(1, 'EUR'),
    );

    $first = $product->flushEvents();
    $second = $product->flushEvents();

    expect($first)->toHaveCount(1)
        ->and($second)->toBeEmpty();
});
