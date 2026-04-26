<?php

declare(strict_types=1);

namespace App\Domain\Communication\Contracts;

use App\Domain\Communication\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification;

    public function save(Notification $notification): void;
}
