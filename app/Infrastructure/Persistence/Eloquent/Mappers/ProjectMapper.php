<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Catalog\Entities\Project as ProjectEntity;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Models\Project as EloquentProject;

/**
 * Translates between the Catalog\Project domain entity and the Eloquent
 * App\Models\Project persistence model.
 */
final class ProjectMapper
{
    public static function toDomain(EloquentProject $model): ProjectEntity
    {
        $metadata = $model->metadata;
        // AsArrayObject cast returns ArrayObject; normalize to array
        $metadataArray = is_object($metadata) ? (array) $metadata : (array) ($metadata ?? []);

        return ProjectEntity::rehydrate(
            id: (string) $model->getKey(),
            slug: new Slug((string) $model->slug),
            name: (string) $model->name,
            description: $model->description !== null ? (string) $model->description : null,
            status: ProjectStatus::from((string) $model->status),
            locale: new Locale((string) $model->locale),
            primaryDomain: $model->primary_domain !== null ? (string) $model->primary_domain : null,
            viralityScore: (int) $model->virality_score,
            valueScore: (int) $model->value_score,
            ownerId: (string) $model->owner_id,
            metadata: $metadataArray,
        );
    }

    public static function applyToEloquent(ProjectEntity $entity, EloquentProject $model): EloquentProject
    {
        $model->slug = $entity->slug->value;
        $model->name = $entity->name;
        $model->description = $entity->description;
        $model->status = $entity->status->value;
        $model->locale = $entity->locale->value;
        $model->primary_domain = $entity->primaryDomain;
        $model->virality_score = $entity->viralityScore;
        $model->value_score = $entity->valueScore;
        $model->owner_id = max(0, (int) $entity->ownerId);
        $model->metadata = $entity->metadata;

        return $model;
    }
}
