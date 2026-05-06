<?php

declare(strict_types=1);

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\DTOs\GitHubRepoInfo;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Application\Catalog\Services\GitHubRepositoryService;
use App\Application\Catalog\Services\ProjectDocumentationGenerator;
use App\Domain\Catalog\Contracts\ProjectRepositoryInterface;
use App\Domain\Catalog\Events\ContentProduced;
use App\Infrastructure\Pipeline\Jobs\DeployProjectJob;
use App\Infrastructure\Pipeline\Jobs\WriteProjectDocumentationJob;
use App\Infrastructure\Pipeline\MockGitHubRepositoryService;
use App\Models\Project as EloquentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('mock GitHub adapter writes a committed file to local storage and returns a deterministic SHA', function (): void {
    Storage::fake('local');

    $repo = new GitHubRepoInfo(
        fullName: 'webfactory-org/sha-test',
        htmlUrl: 'https://github.com/webfactory-org/sha-test',
        sshUrl: 'git@github.com:webfactory-org/sha-test.git',
        defaultBranch: 'main',
    );

    $commit = (new MockGitHubRepositoryService)->commitFile(
        repo: $repo,
        path: 'CLAUDE.md',
        content: '# Hello generated site',
        commitMessage: 'docs: initial CLAUDE.md',
    );

    expect($commit->sha)->toHaveLength(40)
        ->and($commit->path)->toBe('CLAUDE.md')
        ->and($commit->htmlUrl)->toBe('https://github.com/webfactory-org/sha-test/blob/main/CLAUDE.md')
        ->and($commit->message)->toBe('docs: initial CLAUDE.md');

    Storage::disk('local')->assertExists('repos/webfactory-org/sha-test/CLAUDE.md');
    expect(Storage::disk('local')->get('repos/webfactory-org/sha-test/CLAUDE.md'))
        ->toBe('# Hello generated site');

    // Idempotency — same inputs => same SHA
    $second = (new MockGitHubRepositoryService)->commitFile(
        $repo,
        'CLAUDE.md',
        '# Hello generated site',
        'docs: initial CLAUDE.md',
    );
    expect($second->sha)->toBe($commit->sha);
});

it('ProjectDocumentationGenerator renders a CLAUDE.md with the project name, slug, locales and live URL', function (): void {
    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'doc-render',
        'name' => 'Doc Render Test',
        'description' => 'A SaaS for expats',
        'status' => 'deployed',
        'locale' => 'fr-FR',
        'owner_id' => $owner->getKey(),
        'metadata' => [
            'target_locales' => ['fr-FR', 'en-US'],
            'analysis' => ['virality_score' => 80, 'value_score' => 70],
            'blueprint' => [
                'pages' => [
                    ['slug' => 'home', 'title' => 'Home', 'type' => 'static'],
                    ['slug' => 'pricing', 'title' => 'Pricing', 'type' => 'static'],
                ],
            ],
            'design' => ['tokens' => ['color.primary' => '#4F46E5', 'radius.md' => '0.5rem']],
            'brief' => ['file_count' => 42, 'checksum' => str_repeat('a', 64)],
            'github' => [
                'full_name' => 'webfactory-org/doc-render',
                'html_url' => 'https://github.com/webfactory-org/doc-render',
                'ssh_url' => 'git@github.com:webfactory-org/doc-render.git',
                'default_branch' => 'main',
            ],
            'content' => [
                'pages_count' => 4,
                'articles_count' => 2,
                'faqs_count' => 6,
                'produced_locales' => ['fr-FR', 'en-US'],
            ],
            'deployment' => [
                'success' => true,
                'provider' => 'placeholder',
                'live_url' => 'https://doc-render.webfactory.test',
                'preview_url' => null,
                'deployment_id' => 'dep_123',
            ],
        ],
    ]);
    $project = app(ProjectRepositoryInterface::class)->findById((string) $row->getKey());
    assert($project !== null);

    $markdown = app(ProjectDocumentationGenerator::class)->generateClaudeMd($project);

    expect($markdown)->toContain('# CLAUDE.md — Doc Render Test')
        ->and($markdown)->toContain('`doc-render`')
        ->and($markdown)->toContain('`fr-FR`')
        ->and($markdown)->toContain('`en-US`')
        ->and($markdown)->toContain('https://doc-render.webfactory.test')
        ->and($markdown)->toContain('webfactory-org/doc-render')
        ->and($markdown)->toContain('**4** pages')
        ->and($markdown)->toContain('**2** articles')
        ->and($markdown)->toContain('**6** FAQs')
        ->and($markdown)->toContain('color.primary')
        ->and($markdown)->toEndWith("\n");
});

it('WriteProjectDocumentationJob bails out cleanly when github metadata is missing', function (): void {
    Storage::fake('local');

    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'no-gh',
        'name' => 'No GH',
        'status' => 'building',
        'locale' => 'fr',
        'owner_id' => $owner->getKey(),
        'metadata' => [], // no github key
    ]);

    (new WriteProjectDocumentationJob((string) $row->getKey()))->handle(
        app(ProjectRepositoryInterface::class),
        app(ProjectDocumentationGenerator::class),
        app(GitHubRepositoryService::class),
    );

    $row->refresh();
    expect((array) $row->metadata)->not->toHaveKey('documentation');
    Storage::disk('local')->assertMissing('repos/webfactory-org/no-gh/CLAUDE.md');
});

it('WriteProjectDocumentationJob commits CLAUDE.md and persists metadata.documentation', function (): void {
    Storage::fake('local');

    $owner = User::factory()->create();
    $row = EloquentProject::query()->create([
        'slug' => 'doc-job',
        'name' => 'Doc Job Test',
        'description' => 'idea',
        'status' => 'deployed',
        'locale' => 'fr-FR',
        'owner_id' => $owner->getKey(),
        'metadata' => [
            'github' => [
                'full_name' => 'webfactory-org/doc-job',
                'html_url' => 'https://github.com/webfactory-org/doc-job',
                'ssh_url' => 'git@github.com:webfactory-org/doc-job.git',
                'default_branch' => 'main',
            ],
            'content' => ['pages_count' => 1, 'articles_count' => 1, 'faqs_count' => 3, 'produced_locales' => ['fr-FR']],
            'deployment' => ['success' => true, 'provider' => 'placeholder', 'live_url' => 'https://doc-job.webfactory.test'],
        ],
    ]);

    (new WriteProjectDocumentationJob((string) $row->getKey()))->handle(
        app(ProjectRepositoryInterface::class),
        app(ProjectDocumentationGenerator::class),
        app(GitHubRepositoryService::class),
    );

    $row->refresh();
    $meta = (array) $row->metadata;

    expect($meta)->toHaveKey('documentation')
        ->and((array) $meta['documentation'])->toHaveKey('sha')
        ->and((array) $meta['documentation'])->toHaveKey('path')
        ->and((array) $meta['documentation'])->toHaveKey('html_url')
        ->and((array) $meta['documentation'])->toHaveKey('bytes')
        ->and((string) $meta['documentation']['path'])->toBe('CLAUDE.md')
        ->and((int) $meta['documentation']['bytes'])->toBeGreaterThan(500);

    Storage::disk('local')->assertExists('repos/webfactory-org/doc-job/CLAUDE.md');
    expect(Storage::disk('local')->get('repos/webfactory-org/doc-job/CLAUDE.md'))
        ->toContain('Doc Job Test');
});

it('queues WriteProjectDocumentationJob in parallel with deploy when ContentProduced fires', function (): void {
    Bus::fake([WriteProjectDocumentationJob::class, DeployProjectJob::class]);

    Event::dispatch(new ContentProduced(
        projectId: 'proj-evt',
        pagesCount: 2,
        articlesCount: 1,
        faqsCount: 3,
        producedLocales: ['fr-FR'],
    ));

    Bus::assertDispatched(WriteProjectDocumentationJob::class);
    Bus::assertDispatched(DeployProjectJob::class);
});

it('full pipeline ends with metadata.documentation populated', function (): void {
    config(['queue.default' => 'sync']);
    Storage::fake('s3');
    Storage::fake('local');

    $owner = User::factory()->create();

    /** @var CreateProjectHandler $handler */
    $handler = app(CreateProjectHandler::class);
    $project = $handler->handle(new CreateProjectCommand(
        slug: 'docs-e2e',
        name: 'Docs E2E',
        description: str_repeat('a no-code AI tool ', 12),
        locale: 'en-US',
        primaryDomain: 'docs-e2e.local',
        ownerId: (string) $owner->getKey(),
        metadata: [],
    ));

    $row = EloquentProject::query()->find($project->id);
    $meta = (array) $row->metadata;

    expect($meta)->toHaveKey('documentation')
        ->and((string) $meta['documentation']['path'])->toBe('CLAUDE.md');

    Storage::disk('local')->assertExists('repos/webfactory-org/docs-e2e/CLAUDE.md');
});
