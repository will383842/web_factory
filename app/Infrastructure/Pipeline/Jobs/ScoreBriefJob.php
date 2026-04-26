<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\DTOs\BriefBundle;
use App\Application\Catalog\DTOs\BriefScore;
use App\Application\Catalog\Services\BriefScorerService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use App\Domain\Catalog\Events\BriefScored;
use App\Domain\Catalog\Exceptions\BriefScoreTooLowException;
use App\Domain\Shared\Contracts\EventDispatcher as DomainEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\Storage;

/**
 * Pipeline Step 4b — score the brief on completeness; only chain to step 5
 * when the score gates ≥ 85.
 */
final class ScoreBriefJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        BriefScorerService $scorer,
        DomainEventDispatcher $events,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $payload = (string) Storage::disk('s3')->get("projects/{$project->id}/brief.json");
        /** @var array<string, string> $files */
        $files = (array) json_decode($payload, true);

        $checksum = (string) ($project->metadata['brief']['checksum'] ?? sha1($payload));
        $bundle = new BriefBundle(files: $files, checksum: $checksum);

        $score = $scorer->score($project, $bundle);

        $project->metadata = array_merge($project->metadata, ['brief_score' => $score->toMetadataArray()]);
        $projects->save($project);

        $events->dispatchAll($project->flushEvents());
        $events->dispatch(new BriefScored($project->id, $score->score, $score->passes()));

        if (! $score->passes()) {
            $threshold = BriefScore::PASSING_THRESHOLD;
            throw new BriefScoreTooLowException(
                "Brief score {$score->score} is below the {$threshold} threshold.",
            );
        }

        InitGitHubRepoJob::dispatch($project->id);
    }
}
