<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Identity\Entities\User as UserEntity;
use App\Domain\Identity\ValueObjects\Email;
use App\Models\User as EloquentUser;

/**
 * Translates between the Identity\User domain entity and the Laravel Auth
 * Eloquent User model.
 *
 * The Eloquent model lives in App\Models per Laravel + Filament conventions;
 * the ArchTest explicitly allows that location (see tests/Arch/ArchitectureTest).
 */
final class UserMapper
{
    public static function toDomain(EloquentUser $model): UserEntity
    {
        return UserEntity::rehydrate(
            id: (string) $model->getKey(),
            email: new Email((string) $model->email),
            name: (string) $model->name,
        );
    }

    /**
     * Apply domain entity attributes onto an Eloquent model (without saving).
     */
    public static function applyToEloquent(UserEntity $entity, EloquentUser $model): EloquentUser
    {
        $model->name = $entity->name;
        $model->email = $entity->email->value;

        return $model;
    }
}
