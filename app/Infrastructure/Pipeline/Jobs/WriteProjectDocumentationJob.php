<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline\Jobs;

use App\Application\Catalog\DTOs\GitHubRepoInfo;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Application\Catalog\Services\ProjectDocumentationGenerator;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Entities\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

/**
 * Sprint 26 — Per-platform CLAUDE.md generation.
 *
 * Listens to the same `ContentProduced` event as `DeployProjectJob` (parallel
 * branch — order is irrelevant: deploy ships the runtime, this commits the
 * documentation). Reads the project's pipeline metadata, renders a markdown
 * file via {@see ProjectDocumentationGenerator}, and pushes it to the repo
 * root through {@see GitHubRepositoryService::commitFile()}.
 *
 * Persists the commit info under `metadata.documentation` so admins can
 * audit which CLAUDE.md SHA is currently in the generated repo.
 */
final class WriteProjectDocumentationJob implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly string $projectId) {}

    public function handle(
        ProjectRepositoryInterface $projects,
        ProjectDocumentationGenerator $generator,
        GitHubRepositoryService $github,
    ): void {
        $project = $projects->findById($this->projectId);
        if (! $project instanceof Project) {
            return;
        }

        $githubMeta = (array) ($project->metadata['github'] ?? []);
        if (empty($githubMeta['full_name'])) {
            // No repo yet — pipeline step 5 hasn't run. Bail out cleanly.
            return;
        }

        $repo = new GitHubRepoInfo(
            fullName: (string) $githubMeta['full_name'],
            htmlUrl: (string) ($githubMeta['html_url'] ?? ''),
            sshUrl: (string) ($githubMeta['ssh_url'] ?? ''),
            defaultBranch: (string) ($githubMeta['default_branch'] ?? 'main'),
        );

        $markdown = $generator->generateClaudeMd($project);

        $commit = $github->commitFile(
            repo: $repo,
            path: 'CLAUDE.md',
            content: $markdown,
            commitMessage: 'docs(claude): regenerate per-platform CLAUDE.md from pipeline metadata',
        );

        $project->metadata = array_merge($project->metadata, [
            'documentation' => array_merge($commit->toMetadataArray(), [
                'bytes' => strlen($markdown),
            ]),
        ]);

        $projects->save($project);
    }
}
