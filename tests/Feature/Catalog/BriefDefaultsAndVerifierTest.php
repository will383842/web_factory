<?php

declare(strict_types=1);

use App\Application\Catalog\DTOs\WorkspaceVerificationReport;
use App\Application\Catalog\Services\BriefDefaultsInjector;
use App\Application\Catalog\Services\WorkspaceVerifier;
use App\Models\User;
use App\Settings\BriefDefaultsSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function makeSettings(bool $enabled = true): BriefDefaultsSettings
{
    $settings = app(BriefDefaultsSettings::class);
    $settings->injectionEnabled = $enabled;
    $settings->design = "## Design preamble\n\n- Tailwind v4\n- shadcn/ui";
    $settings->production = "## Production preamble\n\n- HTTPS\n- HSTS";
    $settings->accessibility = "## A11y preamble\n\n- WCAG 2.2 AA";
    $settings->seoAeo = "## SEO preamble\n\n- LCP < 1.5s";
    $settings->security = "## Security preamble\n\n- OWASP Top 10";

    return $settings;
}

// ---- BriefDefaultsInjector --------------------------------------------------

it('augments a CLAUDE.md by prepending all 5 preambles in order', function (): void {
    $injector = new BriefDefaultsInjector(makeSettings());

    $augmented = $injector->augment("# My Project\n\nOriginal content here.");

    expect($augmented)->toContain('WEBFACTORY_DEFAULTS_INJECTED')
        ->and($augmented)->toContain('## Design preamble')
        ->and($augmented)->toContain('## Production preamble')
        ->and($augmented)->toContain('## A11y preamble')
        ->and($augmented)->toContain('## SEO preamble')
        ->and($augmented)->toContain('## Security preamble')
        ->and($augmented)->toContain('# ORIGINAL BRIEF')
        ->and($augmented)->toContain('# My Project')
        ->and($augmented)->toContain('Original content here.');

    // Order: design must come before production must come before security
    $designPos = (int) strpos($augmented, '## Design preamble');
    $productionPos = (int) strpos($augmented, '## Production preamble');
    $securityPos = (int) strpos($augmented, '## Security preamble');
    $originalPos = (int) strpos($augmented, '# ORIGINAL BRIEF');

    expect($designPos)->toBeGreaterThan(0)
        ->and($productionPos)->toBeGreaterThan($designPos)
        ->and($securityPos)->toBeGreaterThan($productionPos)
        ->and($originalPos)->toBeGreaterThan($securityPos);
});

it('returns the original verbatim when injection is disabled', function (): void {
    $injector = new BriefDefaultsInjector(makeSettings(enabled: false));

    $original = "# Untouched\n\nNothing should be added.";
    expect($injector->augment($original))->toBe($original);
});

it('is idempotent: re-augmenting an already-augmented file is a no-op', function (): void {
    $injector = new BriefDefaultsInjector(makeSettings());

    $first = $injector->augment("# X\n\nbody");
    $second = $injector->augment($first);

    expect($second)->toBe($first);
});

it('writes the augmented content back to disk via augmentFile()', function (): void {
    $injector = new BriefDefaultsInjector(makeSettings());

    $tmp = sys_get_temp_dir().'/wf-claudemd-'.bin2hex(random_bytes(4)).'.md';
    file_put_contents($tmp, "# Hi\n\nbody");

    expect($injector->augmentFile($tmp))->toBeTrue()
        ->and(file_get_contents($tmp))->toContain('## Design preamble')
        ->and(file_get_contents($tmp))->toContain('# Hi');

    // Idempotent on disk too
    expect($injector->augmentFile($tmp))->toBeFalse();

    @unlink($tmp);
});

// ---- WorkspaceVerifier ------------------------------------------------------

it('returns workspace_missing when the directory does not exist', function (): void {
    $report = (new WorkspaceVerifier)->verify('/nonexistent/path/abcxyz');

    expect($report->workspaceExists)->toBeFalse()
        ->and($report->score)->toBe(0)
        ->and($report->failed)->toHaveKey('workspace_missing')
        ->and($report->isReady())->toBeFalse();
});

it('verifies a minimal workspace with all critical files present', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-mini-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp);
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'X=y');
    File::put($tmp.'/.gitignore', '/vendor');
    File::put($tmp.'/docker-compose.yml', 'services: {}');

    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->workspaceExists)->toBeTrue()
        ->and($report->passed)->toHaveKey('claude_md')
        ->and($report->passed)->toHaveKey('readme')
        ->and($report->passed)->toHaveKey('env_example')
        ->and($report->failed)->toHaveKey('github_workflows')
        ->and($report->warnings)->toHaveKey('backend_skipped')
        ->and($report->warnings)->toHaveKey('frontend_skipped');

    File::deleteDirectory($tmp);
});

it('detects a Laravel backend at root and runs backend checks', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-laravel-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp.'/app');
    File::ensureDirectoryExists($tmp.'/routes');
    File::ensureDirectoryExists($tmp.'/database/migrations');
    File::ensureDirectoryExists($tmp.'/config');
    File::ensureDirectoryExists($tmp.'/tests');
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'x');
    File::put($tmp.'/.gitignore', '/vendor');
    File::put($tmp.'/docker-compose.yml', 'x');
    File::put($tmp.'/composer.json', '{"name":"x/y"}');
    File::put($tmp.'/database/migrations/0001_create_users.php', '<?php');
    foreach (range(1, 6) as $i) {
        File::put($tmp.'/tests/Test'.$i.'.php', '<?php');
    }

    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->passed)->toHaveKey('backend_composer_json')
        ->and($report->passed)->toHaveKey('backend_app_dir')
        ->and($report->passed)->toHaveKey('migrations_present')
        ->and($report->passed)->toHaveKey('tests_present')
        ->and($report->warnings)->not->toHaveKey('backend_skipped')
        ->and($report->warnings)->not->toHaveKey('empty_migrations');

    File::deleteDirectory($tmp);
});

it('detects a frontend at root via package.json', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-front-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp);
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'x');
    File::put($tmp.'/.gitignore', '/node_modules');
    File::put($tmp.'/docker-compose.yml', 'x');
    File::put($tmp.'/package.json', '{"name":"x"}');
    File::put($tmp.'/tsconfig.json', '{}');

    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->passed)->toHaveKey('frontend_package_json')
        ->and($report->passed)->toHaveKey('frontend_tsconfig')
        ->and($report->warnings)->not->toHaveKey('frontend_skipped');

    File::deleteDirectory($tmp);
});

it('detects monorepo layout: backend/ + frontend/ subfolders', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-mono-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp.'/backend/app');
    File::ensureDirectoryExists($tmp.'/backend/routes');
    File::ensureDirectoryExists($tmp.'/backend/database');
    File::ensureDirectoryExists($tmp.'/backend/config');
    File::ensureDirectoryExists($tmp.'/backend/tests');
    File::ensureDirectoryExists($tmp.'/frontend');
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'x');
    File::put($tmp.'/.gitignore', '/vendor');
    File::put($tmp.'/docker-compose.yml', 'x');
    File::put($tmp.'/backend/composer.json', '{"name":"x/y"}');
    File::put($tmp.'/frontend/package.json', '{"name":"x"}');

    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->passed)->toHaveKey('backend_composer_json')
        ->and($report->passed)->toHaveKey('frontend_package_json');

    File::deleteDirectory($tmp);
});

it('warns when migrations or tests directory is empty', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-empty-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp.'/database/migrations');
    File::ensureDirectoryExists($tmp.'/tests');
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/composer.json', '{"name":"x/y"}');
    File::put($tmp.'/docker-compose.yml', 'x');

    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->warnings)->toHaveKey('empty_migrations')
        ->and($report->warnings)->toHaveKey('empty_tests');

    File::deleteDirectory($tmp);
});

it('isReady() returns true only when score >= 80 and no failures', function (): void {
    $report = new WorkspaceVerificationReport(
        workspacePath: '/x',
        workspaceExists: true,
        score: 85,
        passed: ['a' => 'A'],
        failed: [],
        warnings: [],
    );
    expect($report->isReady())->toBeTrue();

    $reportWithFailure = new WorkspaceVerificationReport(
        workspacePath: '/x',
        workspaceExists: true,
        score: 85,
        passed: ['a' => 'A'],
        failed: ['b' => 'B'],
        warnings: [],
    );
    expect($reportWithFailure->isReady())->toBeFalse();
});

// ---- Filament admin page ----------------------------------------------------

it('exposes the manage-brief-defaults Filament page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/manage-brief-defaults')
        ->assertOk();
});
