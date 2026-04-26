<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entities;

use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Catalog\Events\ProjectStatusChanged;
use App\Domain\Catalog\Exceptions\InvalidProjectStatusTransitionException;
use App\Domain\Catalog\ValueObjects\ProjectStatus;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

/**
 * Catalog aggregate root — a customer-submitted request to spin up a new
 * web platform. Travels through the 7-step WebFactory pipeline (see Spec 00):
 *
 *   draft → analyzing → blueprinting → designing → building → deployed
 *                                                            ↘ archived (terminal)
 *
 * The aggregate enforces linear forward-only transitions; "archive" can be
 * reached from any non-terminal state. Persistence is delegated to
 * {@see ProjectRepositoryInterface}.
 */
final class Project extends AggregateRoot
{
    /**
     * @param array<string, mixed> $metadata free-form pipeline payload
     *                                       (briefs, IA answers, generated URLs)
     */
    private function __construct(
        public readonly string $id,
        public readonly Slug $slug,
        public readonly string $name,
        public readonly ?string $description,
        public ProjectStatus $status,
        public readonly Locale $locale,
        public readonly ?string $primaryDomain,
        public int $viralityScore,
        public int $valueScore,
        public readonly string $ownerId,
        public array $metadata,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public static function submit(
        string $id,
        Slug $slug,
        string $name,
        ?string $description,
        Locale $locale,
        ?string $primaryDomain,
        string $ownerId,
        array $metadata = [],
    ): self {
        $project = new self(
            id: $id,
            slug: $slug,
            name: $name,
            description: $description,
            status: ProjectStatus::Draft,
            locale: $locale,
            primaryDomain: $primaryDomain,
            viralityScore: 0,
            valueScore: 0,
            ownerId: $ownerId,
            metadata: $metadata,
        );

        $project->recordEvent(new ProjectCreated($id, $slug, $name, $ownerId));

        return $project;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function rehydrate(
        string $id,
        Slug $slug,
        string $name,
        ?string $description,
        ProjectStatus $status,
        Locale $locale,
        ?string $primaryDomain,
        int $viralityScore,
        int $valueScore,
        string $ownerId,
        array $metadata,
    ): self {
        return new self(
            id: $id,
            slug: $slug,
            name: $name,
            description: $description,
            status: $status,
            locale: $locale,
            primaryDomain: $primaryDomain,
            viralityScore: $viralityScore,
            valueScore: $valueScore,
            ownerId: $ownerId,
            metadata: $metadata,
        );
    }

    public function transitionTo(ProjectStatus $next): void
    {
        if ($this->status === $next) {
            return;
        }

        if (! $this->canTransitionTo($next)) {
            throw new InvalidProjectStatusTransitionException(
                "Invalid project status transition: {$this->status->value} → {$next->value}",
            );
        }

        $previous = $this->status;
        $this->status = $next;
        $this->recordEvent(new ProjectStatusChanged($this->id, $previous, $next));
    }

    public function archive(): void
    {
        if ($this->status === ProjectStatus::Archived) {
            return;
        }
        $previous = $this->status;
        $this->status = ProjectStatus::Archived;
        $this->recordEvent(new ProjectStatusChanged($this->id, $previous, ProjectStatus::Archived));
    }

    public function score(int $virality, int $value): void
    {
        $this->viralityScore = max(0, min(100, $virality));
        $this->valueScore = max(0, min(100, $value));
    }

    private function canTransitionTo(ProjectStatus $next): bool
    {
        if ($next === ProjectStatus::Archived) {
            return $this->status !== ProjectStatus::Archived;
        }

        $forward = [
            ProjectStatus::Draft->value => ProjectStatus::Analyzing,
            ProjectStatus::Analyzing->value => ProjectStatus::Blueprinting,
            ProjectStatus::Blueprinting->value => ProjectStatus::Designing,
            ProjectStatus::Designing->value => ProjectStatus::Building,
            ProjectStatus::Building->value => ProjectStatus::Deployed,
        ];

        return ($forward[$this->status->value] ?? null) === $next;
    }
}
