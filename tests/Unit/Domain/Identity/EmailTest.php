<?php

declare(strict_types=1);

use App\Domain\Identity\Exceptions\InvalidEmailException;
use App\Domain\Identity\ValueObjects\Email;

it('accepts a valid email', function (): void {
    $e = new Email('alice@example.com');
    expect($e->domain())->toBe('example.com')
        ->and((string) $e)->toBe('alice@example.com');
});

it('rejects an invalid email', function (): void {
    new Email('not-an-email');
})->throws(InvalidEmailException::class);

it('compares equality case-insensitively', function (): void {
    expect((new Email('Alice@Example.com'))->equals(new Email('alice@example.com')))
        ->toBeTrue();
});
