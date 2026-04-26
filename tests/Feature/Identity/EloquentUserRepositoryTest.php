<?php

declare(strict_types=1);

use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Identity\Entities\User as DomainUser;
use App\Domain\Identity\ValueObjects\Email;
use App\Models\User as EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->repo = app(UserRepositoryInterface::class);
});

it('returns null for an unknown id', function (): void {
    expect($this->repo->findById('999999'))->toBeNull();
});

it('finds an existing user by id', function (): void {
    $eloquent = EloquentUser::factory()->create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ]);

    $domain = $this->repo->findById((string) $eloquent->getKey());

    expect($domain)->toBeInstanceOf(DomainUser::class)
        ->and($domain->id)->toBe((string) $eloquent->getKey())
        ->and($domain->name)->toBe('Alice')
        ->and($domain->email->value)->toBe('alice@example.com');
});

it('finds a user by email value object', function (): void {
    EloquentUser::factory()->create(['email' => 'bob@example.com']);

    $found = $this->repo->findByEmail(new Email('bob@example.com'));

    expect($found)->not->toBeNull()
        ->and($found->email->value)->toBe('bob@example.com');
});

it('persists a domain user via save()', function (): void {
    // Pre-create the row in Eloquent to obtain a real ID, then mutate via Domain
    $eloquent = EloquentUser::factory()->create(['email' => 'old@example.com', 'name' => 'Old']);
    $domain = DomainUser::rehydrate(
        id: (string) $eloquent->getKey(),
        email: new Email('new@example.com'),
        name: 'NewName',
    );

    $this->repo->save($domain);

    $reloaded = EloquentUser::query()->find($eloquent->getKey());
    expect($reloaded->email)->toBe('new@example.com')
        ->and($reloaded->name)->toBe('NewName');
});

it('deletes a user by id', function (): void {
    $eloquent = EloquentUser::factory()->create();
    $this->repo->delete((string) $eloquent->getKey());

    expect(EloquentUser::query()->find($eloquent->getKey()))->toBeNull();
});
