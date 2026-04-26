<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Slug;

interface ProjectRepositoryInterface
{
    public function findById(string $id): ?Project;

    public function findBySlug(Slug $slug): ?Project;

    public function save(Project $project): void;

    public function delete(string $id): void;

    /**
     * @return list<Project>
     */
    public function findByOwner(string $ownerId, int $limit = 50): array;

    /**
     * @return list<Project>
     */
    public function findByStatus(ProjectStatus $status, int $limit = 50): array;
}
