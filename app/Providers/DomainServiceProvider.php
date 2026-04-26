<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Catalog\Services\BlueprintGenerationService;
use App\Application\Catalog\Services\BriefBuilderService;
use App\Application\Catalog\Services\BriefScorerService;
use App\Application\Catalog\Services\DesignGenerationService;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Application\Catalog\Services\IdeaAnalysisService;
use App\Application\Content\Services\EmbeddingService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Content\Events\ArticlePublished;
use App\Domain\Content\Events\PagePublished;
use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Content\HeuristicEmbeddingService;
use App\Infrastructure\Content\Listeners\IngestPublishedContentToKnowledgeBase;
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
 * Wires Domain contracts to their Infrastructure implementations and binds
 * pipeline + content listeners.
 *
 * Sprint 1: EventDispatcher.
 * Sprint 2: + UserRepositoryInterface (Identity BC).
 * Sprint 4: + ProjectRepositoryInterface (Catalog BC).
 * Sprint 5: + Pipeline ports steps 1-3 + listener.
 * Sprint 6: + Pipeline ports steps 4-5 + listener.
 * Sprint 7: + EmbeddingService + KB auto-import on PagePublished/ArticlePublished.
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);

        $this->app->bind(IdeaAnalysisService::class, HeuristicIdeaAnalysisService::class);
        $this->app->bind(BlueprintGenerationService::class, HeuristicBlueprintGenerationService::class);
        $this->app->bind(DesignGenerationService::class, HeuristicDesignGenerationService::class);

        $this->app->bind(BriefBuilderService::class, HeuristicBriefBuilderService::class);
        $this->app->bind(BriefScorerService::class, HeuristicBriefScorerService::class);
        $this->app->bind(GitHubRepositoryService::class, MockGitHubRepositoryService::class);

        // Sprint 7 — Content / KB
        $this->app->bind(EmbeddingService::class, HeuristicEmbeddingService::class);
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(ProjectCreated::class, StartPipelineOnProjectCreated::class);
        $events->listen(DesignGenerated::class, StartBuildOnDesignGenerated::class);

        // Sprint 7 — KB auto-import on content publication.
        $events->listen(PagePublished::class, [IngestPublishedContentToKnowledgeBase::class, 'handlePagePublished']);
        $events->listen(ArticlePublished::class, [IngestPublishedContentToKnowledgeBase::class, 'handleArticlePublished']);
    }
}
