<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Catalog\Services\BlueprintGenerationService;
use App\Application\Catalog\Services\BriefBuilderService;
use App\Application\Catalog\Services\BriefScorerService;
use App\Application\Catalog\Services\DesignGenerationService;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Application\Catalog\Services\IdeaAnalysisService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Events\LaravelEventDispatcher;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProjectRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Infrastructure\Pipeline\HeuristicBlueprintGenerationService;
use App\Infrastructure\Pipeline\HeuristicBriefBuilderService;
use App\Infrastructure\Pipeline\HeuristicBriefScorerService;
use App\Infrastructure\Pipeline\HeuristicDesignGenerationService;
use App\Infrastructure\Pipeline\HeuristicIdeaAnalysisService;
use App\Infrastructure\Pipeline\Listeners\StartBuildOnDesignGenerated;
use App\Infrastructure\Pipeline\Listeners\StartPipelineOnProjectCreated;
use App\Infrastructure\Pipeline\MockGitHubRepositoryService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

/**
 * Wires Domain contracts (App\Domain\*\Contracts) to their Infrastructure
 * implementations (App\Infrastructure\*) and binds pipeline listeners.
 *
 * Sprint 1: EventDispatcher.
 * Sprint 2: + UserRepositoryInterface (Identity BC).
 * Sprint 4: + ProjectRepositoryInterface (Catalog BC).
 * Sprint 5: + Pipeline ports steps 1-3 (Idea / Blueprint / Design) + listener.
 * Sprint 6: + Pipeline ports steps 4-5 (Brief builder / Scorer / GitHub) + listener.
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Sprint 1
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);

        // Sprint 2 — Identity
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        // Sprint 4 — Catalog persistence
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);

        // Sprint 5 — Pipeline ports steps 1-3 (heuristic placeholders)
        $this->app->bind(IdeaAnalysisService::class, HeuristicIdeaAnalysisService::class);
        $this->app->bind(BlueprintGenerationService::class, HeuristicBlueprintGenerationService::class);
        $this->app->bind(DesignGenerationService::class, HeuristicDesignGenerationService::class);

        // Sprint 6 — Pipeline ports steps 4-5 (heuristic / mock)
        $this->app->bind(BriefBuilderService::class, HeuristicBriefBuilderService::class);
        $this->app->bind(BriefScorerService::class, HeuristicBriefScorerService::class);
        $this->app->bind(GitHubRepositoryService::class, MockGitHubRepositoryService::class);
    }

    public function boot(Dispatcher $events): void
    {
        // Auto-kick the pipeline as soon as a Project is submitted.
        $events->listen(ProjectCreated::class, StartPipelineOnProjectCreated::class);

        // Sprint 6 — chain steps 4-5 right after step 3 finishes.
        $events->listen(DesignGenerated::class, StartBuildOnDesignGenerated::class);
    }
}
