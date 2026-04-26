<?php

declare(strict_types=1);

namespace App\Application\Identity\Handlers;

use App\Application\Identity\Commands\RegisterUserCommand;
use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\Hash;

/**
 * Use case: register a new user.
 *
 * 1. Coerce primitive input into Domain VOs (Email).
 * 2. Persist credentials via Eloquent in a single insert — password storage
 *    is a deliberate Infrastructure concern and stays out of the Domain entity.
 * 3. Build the aggregate via factory `User::register()` using the persisted
 *    primary key — this records the `UserRegistered` domain event.
 * 4. Dispatch recorded events through the {@see EventDispatcher} port.
 *
 * Subsequent state changes (name update, etc.) go through
 * {@see UserRepositoryInterface::save()}.
 */
final readonly class RegisterUserHandler
{
    public function __construct(
        private EventDispatcher $events,
    ) {}

    public function handle(RegisterUserCommand $command): User
    {
        $email = new Email($command->email);

        $model = EloquentUser::query()->create([
            'name' => $command->name,
            'email' => $email->value,
            'password' => Hash::make($command->plainPassword),
        ]);

        $user = User::register((string) $model->getKey(), $email, $command->name);
        $this->events->dispatchAll($user->flushEvents());

        return $user;
    }
}
