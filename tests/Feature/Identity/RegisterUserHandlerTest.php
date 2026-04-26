<?php

declare(strict_types=1);

use App\Application\Identity\Commands\RegisterUserCommand;
use App\Application\Identity\Handlers\RegisterUserHandler;
use App\Domain\Identity\Events\UserRegistered;
use App\Models\User as EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers a new user, persists it, hashes the password, and dispatches UserRegistered', function (): void {
    Event::fake([UserRegistered::class]);

    /** @var RegisterUserHandler $handler */
    $handler = app(RegisterUserHandler::class);

    $domainUser = $handler->handle(new RegisterUserCommand(
        email: 'new@example.com',
        name: 'New User',
        plainPassword: 'super-secret-12',
    ));

    // Domain side
    expect($domainUser->email->value)->toBe('new@example.com')
        ->and($domainUser->name)->toBe('New User');

    // Persistence side
    $row = EloquentUser::query()->where('email', 'new@example.com')->first();
    expect($row)->not->toBeNull()
        ->and(Hash::check('super-secret-12', $row->password))->toBeTrue();

    // Event side
    Event::assertDispatched(UserRegistered::class, function (UserRegistered $e) use ($domainUser): bool {
        return $e->aggregateId() === $domainUser->id
            && $e->email->value === 'new@example.com'
            && $e->eventName() === 'identity.user.registered';
    });
});
