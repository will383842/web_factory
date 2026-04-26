<?php

declare(strict_types=1);

namespace App\Application\Catalog\Handlers;

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;
use App\Models\Project as EloquentProject;

/**
 * Use case: submit a new Catalog\Project (status = draft).
 *
 * 1. Coerce primitive input into Domain VOs (Slug, Locale).
 * 2. Persist via Eloquent so the bigint ID is assigned by Postgres.
 * 3. Build the aggregate via Project::submit() (records ProjectCreated).
 * 4. Dispatch the recorded events through the EventDispatcher port.
 *
 * Subsequent state changes (status transitions, scoring, archive) go through
 * {@see ProjectRepositoryInterface::save()} after mutating the aggregate.
 */
final readonly class CreateProjectHandler
{
    public function __construct(
        private EventDispatcher $events,
    ) {}

    public function handle(CreateProjectCommand $command): Project
    {
        $slug = new Slug($command->slug);
        $locale = new Locale($command->locale);

        // Persist first to obtain the bigint ID
        $model = EloquentProject::query()->create([
            'slug' => $slug->value,
            'name' => $command->name,
            'description' => $command->description,
            'status' => 'draft',
            'locale' => $locale->value,
            'primary_domain' => $command->primaryDomain,
            'virality_score' => 0,
            'value_score' => 0,
            'owner_id' => (int) $command->ownerId,
            'metadata' => $command->metadata,
        ]);

        $project = Project::submit(
            id: (string) $model->getKey(),
            slug: $slug,
            name: $command->name,
            description: $command->description,
            locale: $locale,
            primaryDomain: $command->primaryDomain,
            ownerId: $command->ownerId,
            metadata: $command->metadata,
        );

        $this->events->dispatchAll($project->flushEvents());

        return $project;
    }
}
