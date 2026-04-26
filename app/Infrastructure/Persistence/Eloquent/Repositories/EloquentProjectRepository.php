<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project as ProjectEntity;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Slug;
use App\Infrastructure\Persistence\Eloquent\Mappers\ProjectMapper;
use App\Models\Project as EloquentProject;

/**
 * Eloquent adapter satisfying {@see ProjectRepositoryInterface}.
 */
final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function findById(string $id): ?ProjectEntity
    {
        $model = EloquentProject::query()->find($id);

        return $model === null ? null : ProjectMapper::toDomain($model);
    }

    public function findBySlug(Slug $slug): ?ProjectEntity
    {
        $model = EloquentProject::query()
            ->where('slug', $slug->value)
            ->first();

        return $model === null ? null : ProjectMapper::toDomain($model);
    }

    public function save(ProjectEntity $project): void
    {
        $model = EloquentProject::query()->find($project->id) ?? new EloquentProject;
        ProjectMapper::applyToEloquent($project, $model);
        $model->save();
    }

    public function delete(string $id): void
    {
        EloquentProject::query()->whereKey($id)->delete();
    }

    /**
     * @return list<ProjectEntity>
     */
    public function findByOwner(string $ownerId, int $limit = 50): array
    {
        return array_values(EloquentProject::query()
            ->where('owner_id', $ownerId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(static fn (EloquentProject $m): ProjectEntity => ProjectMapper::toDomain($m))
            ->all());
    }

    /**
     * @return list<ProjectEntity>
     */
    public function findByStatus(ProjectStatus $status, int $limit = 50): array
    {
        return array_values(EloquentProject::query()
            ->where('status', $status->value)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(static fn (EloquentProject $m): ProjectEntity => ProjectMapper::toDomain($m))
            ->all());
    }
}
