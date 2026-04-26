<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\InvalidCurrencyException;
use App\Domain\Shared\Exceptions\MoneyCurrencyMismatchException;
use App\Domain\Shared\ValueObjects\Money;

it('builds from minor units + ISO currency', function (): void {
    $m = Money::fromMinor(1234, 'EUR');
    expect($m->amountMinor)->toBe(1234)
        ->and($m->currency->iso)->toBe('EUR');
});

it('rejects an invalid currency code', function (): void {
    Money::fromMinor(100, 'EU'); // 2 letters
})->throws(InvalidCurrencyException::class);

it('adds amounts in the same currency', function (): void {
    $sum = Money::fromMinor(100, 'EUR')->add(Money::fromMinor(250, 'EUR'));
    expect($sum->amountMinor)->toBe(350);
});

it('refuses to add amounts in different currencies', function (): void {
    Money::fromMinor(100, 'EUR')->add(Money::fromMinor(100, 'USD'));
})->throws(MoneyCurrencyMismatchException::class);

it('compares equality on amount + currency', function (): void {
    $a = Money::fromMinor(500, 'EUR');
    $b = Money::fromMinor(500, 'EUR');
    $c = Money::fromMinor(500, 'USD');
    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
