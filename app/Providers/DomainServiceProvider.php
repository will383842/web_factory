<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Billing\Services\BillingGateway;
use App\Application\Billing\Services\BillingWebhookProcessor;
use App\Application\Catalog\Services\BlueprintGenerationService;
use App\Application\Catalog\Services\BriefBuilderService;
use App\Application\Catalog\Services\BriefScorerService;
use App\Application\Catalog\Services\DesignGenerationService;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Application\Catalog\Services\IdeaAnalysisService;
use App\Application\Content\Services\EmbeddingService;
use App\Application\Content\Services\KnowledgeBaseSearchService;
use App\Application\Identity\Services\SsoProviderRegistry;
use App\Application\Marketing\Services\IndexNowPingService;
use App\Application\Operations\Services\BackupService;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\DesignGenerated;
use App\Domain\Catalog\Events\ProjectCreated;
use App\Domain\Content\Events\ArticlePublished;
use App\Domain\Content\Events\PagePublished;
use App\Domain\Identity\Contracts\UserRepositoryInterface;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Infrastructure\Billing\IdempotentBillingWebhookProcessor;
use App\Infrastructure\Billing\PlaceholderStripeBillingGateway;
use App\Infrastructure\Content\HeuristicEmbeddingService;
use App\Infrastructure\Content\Listeners\IngestPublishedContentToKnowledgeBase;
use App\Infrastructure\Content\PgVectorKnowledgeBase;
use App\Infrastructure\Events\LaravelEventDispatcher;
use App\Infrastructure\Identity\PlaceholderSsoProvider;
use App\Infrastructure\Marketing\LogIndexNowPingService;
use App\Infrastructure\Operations\LocalFilesystemBackupService;
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
 * Sprint 1: EventDispatcher.
 * Sprint 2: + UserRepositoryInterface.
 * Sprint 4: + ProjectRepositoryInterface.
 * Sprint 5: + Pipeline ports steps 1-3 + listener.
 * Sprint 6: + Pipeline ports steps 4-5 + listener.
 * Sprint 7: + EmbeddingService + KB ports + auto-import listeners.
 * Sprint 8: + KnowledgeBaseSearchService binding + IndexNow port.
 * Sprint 12: + BackupService (local-filesystem placeholder).
 * Sprint 13.1: + BillingGateway + BillingWebhookProcessor (Stripe placeholder + idempotent webhook intake).
 * Sprint 13.2: + SsoProviderRegistry (Google/Microsoft/Apple/Okta/GitHub placeholder drivers).
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

        $this->app->bind(EmbeddingService::class, HeuristicEmbeddingService::class);
        $this->app->bind(KnowledgeBaseSearchService::class, PgVectorKnowledgeBase::class);

        // Sprint 8 — Marketing
        $this->app->bind(IndexNowPingService::class, LogIndexNowPingService::class);

        // Sprint 12 — Operations / Backups
        $this->app->bind(BackupService::class, LocalFilesystemBackupService::class);

        // Sprint 13.1 — Billing
        $this->app->bind(BillingGateway::class, PlaceholderStripeBillingGateway::class);
        $this->app->bind(BillingWebhookProcessor::class, IdempotentBillingWebhookProcessor::class);

        // Sprint 13.2 — Identity / SSO (5 placeholder providers, swap → Socialite Sprint 16)
        $this->app->singleton(SsoProviderRegistry::class, function (): SsoProviderRegistry {
            $registry = new SsoProviderRegistry;
            foreach (['google', 'microsoft', 'apple', 'okta', 'github'] as $name) {
                $registry->register(new PlaceholderSsoProvider($name));
            }

            return $registry;
        });
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(ProjectCreated::class, StartPipelineOnProjectCreated::class);
        $events->listen(DesignGenerated::class, StartBuildOnDesignGenerated::class);

        $events->listen(PagePublished::class, [IngestPublishedContentToKnowledgeBase::class, 'handlePagePublished']);
        $events->listen(ArticlePublished::class, [IngestPublishedContentToKnowledgeBase::class, 'handleArticlePublished']);
    }
}
