<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\InvalidUrlException;
use App\Domain\Shared\ValueObjects\Url;

it('accepts a valid https URL', function (): void {
    $url = new Url('https://example.com/path?q=1');
    expect($url->host())->toBe('example.com')
        ->and($url->scheme())->toBe('https');
});

it('rejects non-http schemes', function (): void {
    new Url('ftp://example.com');
})->throws(InvalidUrlException::class);

it('rejects malformed URLs', function (): void {
    new Url('not a url');
})->throws(InvalidUrlException::class);
