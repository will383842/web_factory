<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Identity\Entities\User as UserEntity;
use App\Domain\Identity\ValueObjects\Email;
use App\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Models\User as EloquentUser;

/**
 * Eloquent adapter satisfying the {@see UserRepositoryInterface} port.
 *
 * The Identity\User domain entity is decoupled from Eloquent: this repository
 * is the only place where the bigint primary key is exposed as a string
 * identifier and where the Eloquent model is read/written.
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?UserEntity
    {
        $model = EloquentUser::query()->find($id);

        return $model === null ? null : UserMapper::toDomain($model);
    }

    public function findByEmail(Email $email): ?UserEntity
    {
        $model = EloquentUser::query()
            ->where('email', $email->value)
            ->first();

        return $model === null ? null : UserMapper::toDomain($model);
    }

    public function save(UserEntity $user): void
    {
        $model = EloquentUser::query()->find($user->id) ?? new EloquentUser;
        UserMapper::applyToEloquent($user, $model);
        $model->save();
    }

    public function delete(string $id): void
    {
        EloquentUser::query()->whereKey($id)->delete();
    }
}
