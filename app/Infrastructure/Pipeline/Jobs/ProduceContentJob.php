<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\Services\ContentProductionService;
use App\Application\Shared\Services\AudienceContextService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\ContentProduced;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Sprint 15 — Pipeline Step 6: produce multilingual content from blueprint.
 *
 * Locale set strategy:
 *   1. The project's primary locale (always included)
 *   2. Plus any locales declared in `metadata.target_locales`
 *   3. Fallback: same-language alternatives from AudienceContextService
 */
final class ProduceContentJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        ContentProductionService $producer,
        DomainEventDispatcher $events,
        AudienceContextService $audience,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $locales = $this->resolveLocales($project, $audience);

        $bundle = $producer->produce($project, $locales);

        $project->metadata = array_merge($project->metadata, [
            'content' => $bundle->toMetadataArray(),
        ]);
        $projects->save($project);

        $events->dispatch(new ContentProduced(
            projectId: $project->id,
            pagesCount: count($bundle->pageIds),
            articlesCount: count($bundle->articleIds),
            faqsCount: count($bundle->faqIds),
            producedLocales: $bundle->producedLocales,
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveLocales(Project $project, AudienceContextService $audience): array
    {
        $primary = $project->locale->value;
        $declared = (array) ($project->metadata['target_locales'] ?? []);

        $set = array_values(array_unique(array_merge([$primary], array_map('strval', $declared))));

        // Filter to supported locales only
        $supported = $audience->supportedLocales();

        return array_values(array_filter(
            $set,
            fn (string $loc): bool => in_array($loc, $supported, true),
        )) ?: [$primary];
    }
}
