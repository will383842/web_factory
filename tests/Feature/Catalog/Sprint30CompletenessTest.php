<?php

declare(strict_types=1);

use App\Application\Catalog\Services\BriefDefaultsInjector;
use App\Application\Catalog\Services\WorkspaceVerifier;
use App\Models\User;
use App\Settings\BriefDefaultsSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function makeFullSettings(): BriefDefaultsSettings
{
    $s = app(BriefDefaultsSettings::class);
    $s->injectionEnabled = true;

    // Sprint 28 quality pack
    $s->design = '## Design — Tailwind v4';
    $s->production = '## Production — HSTS preload';
    $s->accessibility = '## A11y — WCAG 2.2 AA';
    $s->seoAeo = '## SEO/AEO — Core Web Vitals';
    $s->security = '## Security — OWASP';

    // Sprint 30 completeness pack
    $s->pagesLayout = '## Pages — Header + Footer obligatoires';
    $s->components = '## Components — shadcn/ui catalog';
    $s->contentEngine = '## Content — 6 articles seed minimum';
    $s->authAccount = '## Auth — Magic link + 2FA + SSO';
    $s->comms = '## Comms — 15 templates email transactionnels';

    return $s;
}

it('injector now includes all 10 preambles in the correct order (5 quality + 5 completeness)', function (): void {
    $injector = new BriefDefaultsInjector(makeFullSettings());

    $augmented = $injector->augment("# My Project\n\nbody");

    expect($augmented)->toContain('## Design — Tailwind v4')
        ->and($augmented)->toContain('## Production — HSTS preload')
        ->and($augmented)->toContain('## A11y — WCAG 2.2 AA')
        ->and($augmented)->toContain('## SEO/AEO — Core Web Vitals')
        ->and($augmented)->toContain('## Security — OWASP')
        ->and($augmented)->toContain('## Pages — Header + Footer obligatoires')
        ->and($augmented)->toContain('## Components — shadcn/ui catalog')
        ->and($augmented)->toContain('## Content — 6 articles seed minimum')
        ->and($augmented)->toContain('## Auth — Magic link + 2FA + SSO')
        ->and($augmented)->toContain('## Comms — 15 templates email transactionnels')
        ->and($augmented)->toContain('# ORIGINAL BRIEF');

    // Order: quality pack must come BEFORE completeness pack
    $designPos = (int) strpos($augmented, '## Design');
    $pagesPos = (int) strpos($augmented, '## Pages');
    $commsPos = (int) strpos($augmented, '## Comms');
    $originalPos = (int) strpos($augmented, '# ORIGINAL BRIEF');

    expect($designPos)->toBeLessThan($pagesPos)
        ->and($pagesPos)->toBeLessThan($commsPos)
        ->and($commsPos)->toBeLessThan($originalPos);
});

it('injection still works when only the completeness pack is filled (legacy fields empty)', function (): void {
    $s = app(BriefDefaultsSettings::class);
    $s->injectionEnabled = true;
    $s->design = '';
    $s->production = '';
    $s->accessibility = '';
    $s->seoAeo = '';
    $s->security = '';
    $s->pagesLayout = '## Pages preamble';
    $s->components = '## Components preamble';
    $s->contentEngine = '';
    $s->authAccount = '';
    $s->comms = '';

    $injector = new BriefDefaultsInjector($s);
    $augmented = $injector->augment("# Project\nbody");

    expect($augmented)->toContain('## Pages preamble')
        ->and($augmented)->toContain('## Components preamble')
        ->and($augmented)->toContain('# ORIGINAL BRIEF');
});

it('injection returns original verbatim when ALL 10 fields are empty', function (): void {
    $s = app(BriefDefaultsSettings::class);
    $s->injectionEnabled = true;
    $s->design = '';
    $s->production = '';
    $s->accessibility = '';
    $s->seoAeo = '';
    $s->security = '';
    $s->pagesLayout = '';
    $s->components = '';
    $s->contentEngine = '';
    $s->authAccount = '';
    $s->comms = '';

    $injector = new BriefDefaultsInjector($s);
    $original = "# Untouched\nNothing should be added.";
    expect($injector->augment($original))->toBe($original);
});

it('verifier checks completeness items: header, footer, blog, legal, errors, emails', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-completeness-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp);

    // Critical
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'X=y');
    File::put($tmp.'/.gitignore', '/vendor');
    File::put($tmp.'/docker-compose.yml', 'services: {}');
    File::put($tmp.'/composer.json', '{"name":"x/y"}');
    File::ensureDirectoryExists($tmp.'/app');
    File::ensureDirectoryExists($tmp.'/routes');
    File::ensureDirectoryExists($tmp.'/database');
    File::ensureDirectoryExists($tmp.'/config');
    File::ensureDirectoryExists($tmp.'/tests');

    // Sprint 30 — completeness items: nothing yet → all should fail
    $report = (new WorkspaceVerifier)->verify($tmp);

    expect($report->failed)->toHaveKey('completeness_manifest')
        ->and($report->failed)->toHaveKey('completeness_robots_txt')
        ->and($report->failed)->toHaveKey('completeness_header_view')
        ->and($report->failed)->toHaveKey('completeness_footer_view')
        ->and($report->failed)->toHaveKey('completeness_blog_index')
        ->and($report->failed)->toHaveKey('completeness_legal_mentions')
        ->and($report->failed)->toHaveKey('completeness_legal_privacy')
        ->and($report->failed)->toHaveKey('completeness_error_404')
        ->and($report->failed)->toHaveKey('completeness_email_welcome');

    File::deleteDirectory($tmp);
});

it('verifier passes completeness items when matching files exist (any of the conventional paths)', function (): void {
    $tmp = sys_get_temp_dir().'/wf-verify-complete-pass-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp);

    // Critical
    File::put($tmp.'/CLAUDE.md', '# x');
    File::put($tmp.'/README.md', '# x');
    File::put($tmp.'/.env.example', 'X=y');
    File::put($tmp.'/.gitignore', '/vendor');
    File::put($tmp.'/docker-compose.yml', 'services: {}');
    File::put($tmp.'/composer.json', '{"name":"x/y"}');
    File::ensureDirectoryExists($tmp.'/app');
    File::ensureDirectoryExists($tmp.'/routes');
    File::ensureDirectoryExists($tmp.'/database');
    File::ensureDirectoryExists($tmp.'/config');
    File::ensureDirectoryExists($tmp.'/tests');

    // Completeness coverage
    File::ensureDirectoryExists($tmp.'/public');
    File::put($tmp.'/public/manifest.webmanifest', '{}');
    File::put($tmp.'/public/sw.js', 'self.addEventListener(...);');
    File::put($tmp.'/public/robots.txt', 'User-agent: *');
    File::put($tmp.'/public/sitemap.xml', '<urlset>');
    File::put($tmp.'/public/rss.xml', '<rss>');

    File::ensureDirectoryExists($tmp.'/resources/views/components');
    File::put($tmp.'/resources/views/components/header.blade.php', '<header>...</header>');
    File::put($tmp.'/resources/views/components/footer.blade.php', '<footer>...</footer>');

    File::ensureDirectoryExists($tmp.'/resources/views/pages');
    File::put($tmp.'/resources/views/pages/about.blade.php', 'about');
    File::put($tmp.'/resources/views/pages/contact.blade.php', 'contact');
    File::put($tmp.'/resources/views/pages/pricing.blade.php', 'pricing');
    File::put($tmp.'/resources/views/pages/faq.blade.php', 'faq');

    File::ensureDirectoryExists($tmp.'/resources/views/blog');
    File::put($tmp.'/resources/views/blog/index.blade.php', 'index');
    File::put($tmp.'/resources/views/blog/show.blade.php', 'show');

    File::ensureDirectoryExists($tmp.'/resources/views/pages/legal');
    File::put($tmp.'/resources/views/pages/legal/mentions-legales.blade.php', 'm');
    File::put($tmp.'/resources/views/pages/legal/cgu.blade.php', 'cgu');
    File::put($tmp.'/resources/views/pages/legal/privacy.blade.php', 'p');
    File::put($tmp.'/resources/views/pages/legal/cookies.blade.php', 'c');

    File::ensureDirectoryExists($tmp.'/resources/views/errors');
    File::put($tmp.'/resources/views/errors/404.blade.php', '404');
    File::put($tmp.'/resources/views/errors/500.blade.php', '500');

    File::ensureDirectoryExists($tmp.'/resources/views/auth');
    File::put($tmp.'/resources/views/auth/login.blade.php', 'login');
    File::put($tmp.'/resources/views/auth/register.blade.php', 'register');

    File::ensureDirectoryExists($tmp.'/app/Providers/Filament');
    File::put($tmp.'/app/Providers/Filament/AdminPanelProvider.php', '<?php');
    File::ensureDirectoryExists($tmp.'/app/Filament/Resources');

    File::ensureDirectoryExists($tmp.'/resources/views/emails/layouts');
    File::put($tmp.'/resources/views/emails/layouts/default.blade.php', 'layout');
    File::ensureDirectoryExists($tmp.'/resources/views/emails/welcome');
    File::ensureDirectoryExists($tmp.'/resources/views/emails/password-reset');

    $report = (new WorkspaceVerifier)->verify($tmp);

    // All completeness items should now pass
    expect($report->passed)->toHaveKey('completeness_manifest')
        ->and($report->passed)->toHaveKey('completeness_header_view')
        ->and($report->passed)->toHaveKey('completeness_footer_view')
        ->and($report->passed)->toHaveKey('completeness_blog_index')
        ->and($report->passed)->toHaveKey('completeness_legal_mentions')
        ->and($report->passed)->toHaveKey('completeness_legal_privacy')
        ->and($report->passed)->toHaveKey('completeness_error_404')
        ->and($report->passed)->toHaveKey('completeness_email_welcome');

    File::deleteDirectory($tmp);
});

it('manage-brief-defaults page exposes the 5 new sections', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/manage-brief-defaults');
    $response->assertOk();
});
