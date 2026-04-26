<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Contracts;

use App\Domain\Compliance\Entities\AuditLog;

interface AuditLogRepositoryInterface
{
    public function findById(string $id): ?AuditLog;

    /**
     * @return list<AuditLog>
     */
    public function findByActor(string $actorId): array;

    public function save(AuditLog $log): void;
}
