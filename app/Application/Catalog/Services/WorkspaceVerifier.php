<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\WorkspaceVerificationReport;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Sprint 28 — Static verification of a generated project workspace.
 *
 * Runs **purely on filesystem inspection** (no subprocess). Designed to
 * answer: "Did Claude Code produce a project that looks structurally sane,
 * or did it leave gaps?". Each check returns pass/fail/warning.
 *
 * Dynamic checks (composer install, run tests, lint, build) belong to
 * a future Quality Gate job — not here.
 *
 * Checks are stack-aware (Laravel + Next.js by default, since that is the
 * baseline imposed by the design preamble).
 */
final class WorkspaceVerifier
{
    /**
     * Critical checks — failures push the score down hard and block "ready".
     *
     * @var array<string, array{path: string, kind: 'file'|'dir', label: string, weight: int}>
     */
    private const CRITICAL_CHECKS = [
        'claude_md' => ['path' => 'CLAUDE.md', 'kind' => 'file', 'label' => 'CLAUDE.md at workspace root', 'weight' => 5],
        'readme' => ['path' => 'README.md', 'kind' => 'file', 'label' => 'README.md', 'weight' => 4],
        'env_example' => ['path' => '.env.example', 'kind' => 'file', 'label' => '.env.example', 'weight' => 4],
        'gitignore' => ['path' => '.gitignore', 'kind' => 'file', 'label' => '.gitignore', 'weight' => 3],
        'docker_compose' => ['path' => 'docker-compose.yml', 'kind' => 'file', 'label' => 'docker-compose.yml (dev)', 'weight' => 4],
    ];

    /**
     * Backend Laravel checks (only checked if `backend/` or `composer.json` exists).
     *
     * @var array<string, array{path: string, kind: 'file'|'dir', label: string, weight: int}>
     */
    private const BACKEND_CHECKS = [
        'composer_json' => ['path' => 'composer.json', 'kind' => 'file', 'label' => 'composer.json', 'weight' => 5],
        'app_dir' => ['path' => 'app', 'kind' => 'dir', 'label' => 'app/ directory', 'weight' => 5],
        'routes_dir' => ['path' => 'routes', 'kind' => 'dir', 'label' => 'routes/', 'weight' => 4],
        'database_dir' => ['path' => 'database', 'kind' => 'dir', 'label' => 'database/', 'weight' => 4],
        'config_dir' => ['path' => 'config', 'kind' => 'dir', 'label' => 'config/', 'weight' => 3],
        'tests_dir' => ['path' => 'tests', 'kind' => 'dir', 'label' => 'tests/ (Pest/PHPUnit)', 'weight' => 5],
        'pint_config' => ['path' => 'pint.json', 'kind' => 'file', 'label' => 'pint.json', 'weight' => 2],
        'phpstan_config' => ['path' => 'phpstan.neon', 'kind' => 'file', 'label' => 'phpstan.neon', 'weight' => 3],
        'phpunit_config' => ['path' => 'phpunit.xml', 'kind' => 'file', 'label' => 'phpunit.xml', 'weight' => 3],
    ];

    /**
     * Frontend checks (only checked if `frontend/` or `package.json` exists).
     *
     * @var array<string, array{path: string, kind: 'file'|'dir', label: string, weight: int}>
     */
    private const FRONTEND_CHECKS = [
        'package_json' => ['path' => 'package.json', 'kind' => 'file', 'label' => 'package.json', 'weight' => 5],
        'tsconfig' => ['path' => 'tsconfig.json', 'kind' => 'file', 'label' => 'tsconfig.json (TypeScript strict)', 'weight' => 3],
        'tailwind_config' => ['path' => 'tailwind.config.ts', 'kind' => 'file', 'label' => 'tailwind.config.ts', 'weight' => 2],
        'eslint_config' => ['path' => 'eslint.config.js', 'kind' => 'file', 'label' => 'eslint.config.js', 'weight' => 2],
        'prettierrc' => ['path' => '.prettierrc', 'kind' => 'file', 'label' => '.prettierrc', 'weight' => 1],
    ];

    /**
     * CI / DevOps checks.
     *
     * @var array<string, array{path: string, kind: 'file'|'dir', label: string, weight: int}>
     */
    private const DEVOPS_CHECKS = [
        'github_workflows' => ['path' => '.github/workflows', 'kind' => 'dir', 'label' => '.github/workflows/ (CI)', 'weight' => 4],
        'dependabot' => ['path' => '.github/dependabot.yml', 'kind' => 'file', 'label' => '.github/dependabot.yml', 'weight' => 1],
        'editorconfig' => ['path' => '.editorconfig', 'kind' => 'file', 'label' => '.editorconfig', 'weight' => 1],
    ];

    /**
     * Sprint 30 — Completeness checks. Each check searches recursively under
     * the backend root for ANY file matching the pattern (Blade view, Vue/Tsx
     * component, route definition, etc.). Stack-aware — Laravel-flavored.
     *
     * @var array<string, array{patterns: list<string>, label: string, weight: int}>
     */
    private const COMPLETENESS_CHECKS = [
        // PWA + tech routes
        'manifest' => ['patterns' => ['public/manifest.webmanifest', 'public/manifest.json'], 'label' => 'PWA manifest', 'weight' => 2],
        'service_worker' => ['patterns' => ['public/sw.js'], 'label' => 'Service worker (PWA)', 'weight' => 2],
        'robots_txt' => ['patterns' => ['public/robots.txt'], 'label' => 'robots.txt', 'weight' => 2],
        'sitemap' => ['patterns' => ['public/sitemap.xml', 'app/Http/Controllers/SitemapController.php'], 'label' => 'sitemap.xml or controller', 'weight' => 3],
        'rss' => ['patterns' => ['public/rss.xml', 'app/Http/Controllers/RssController.php'], 'label' => 'RSS feed', 'weight' => 1],

        // Header / Footer — search for any view containing the keyword
        'header_view' => ['patterns' => ['resources/views/components/header.blade.php', 'resources/views/layouts/partials/header.blade.php', 'resources/views/components/site-header.blade.php'], 'label' => 'Header component', 'weight' => 3],
        'footer_view' => ['patterns' => ['resources/views/components/footer.blade.php', 'resources/views/layouts/partials/footer.blade.php', 'resources/views/components/site-footer.blade.php'], 'label' => 'Footer component', 'weight' => 3],

        // Required public pages — Blade or controller routes
        'page_about' => ['patterns' => ['resources/views/pages/about.blade.php', 'resources/views/about.blade.php', 'resources/views/public/about.blade.php'], 'label' => 'About page', 'weight' => 2],
        'page_contact' => ['patterns' => ['resources/views/pages/contact.blade.php', 'resources/views/contact.blade.php', 'resources/views/public/contact.blade.php'], 'label' => 'Contact page', 'weight' => 2],
        'page_pricing' => ['patterns' => ['resources/views/pages/pricing.blade.php', 'resources/views/pricing.blade.php', 'resources/views/public/pricing.blade.php'], 'label' => 'Pricing page', 'weight' => 1],
        'page_faq' => ['patterns' => ['resources/views/pages/faq.blade.php', 'resources/views/faq.blade.php', 'resources/views/public/faq.blade.php'], 'label' => 'FAQ page', 'weight' => 1],

        // Blog
        'blog_index' => ['patterns' => ['resources/views/blog/index.blade.php', 'resources/views/pages/blog.blade.php', 'app/Http/Controllers/BlogController.php'], 'label' => 'Blog listing', 'weight' => 3],
        'blog_show' => ['patterns' => ['resources/views/blog/show.blade.php', 'resources/views/pages/blog/show.blade.php', 'resources/views/blog/article.blade.php'], 'label' => 'Blog article view', 'weight' => 3],

        // Legal pages — REQUIRED in EU
        'legal_mentions' => ['patterns' => ['resources/views/pages/legal/mentions-legales.blade.php', 'resources/views/legal/mentions.blade.php', 'resources/views/pages/mentions-legales.blade.php'], 'label' => 'Mentions légales / Legal mentions', 'weight' => 4],
        'legal_terms' => ['patterns' => ['resources/views/pages/legal/cgu.blade.php', 'resources/views/legal/terms.blade.php', 'resources/views/pages/cgu.blade.php', 'resources/views/pages/terms.blade.php'], 'label' => 'CGU / Terms of service', 'weight' => 4],
        'legal_privacy' => ['patterns' => ['resources/views/pages/legal/privacy.blade.php', 'resources/views/legal/privacy.blade.php', 'resources/views/pages/politique-confidentialite.blade.php', 'resources/views/pages/privacy.blade.php'], 'label' => 'Privacy policy', 'weight' => 4],
        'legal_cookies' => ['patterns' => ['resources/views/pages/legal/cookies.blade.php', 'resources/views/legal/cookies.blade.php', 'resources/views/pages/politique-cookies.blade.php'], 'label' => 'Cookie policy', 'weight' => 3],

        // Error pages — must be CUSTOM, not Laravel default
        'error_404' => ['patterns' => ['resources/views/errors/404.blade.php'], 'label' => 'Custom 404 page', 'weight' => 3],
        'error_500' => ['patterns' => ['resources/views/errors/500.blade.php'], 'label' => 'Custom 500 page', 'weight' => 2],

        // Auth pages
        'auth_login' => ['patterns' => ['resources/views/auth/login.blade.php', 'app/Http/Controllers/Auth/AuthController.php'], 'label' => 'Login page or controller', 'weight' => 3],
        'auth_register' => ['patterns' => ['resources/views/auth/register.blade.php'], 'label' => 'Register page', 'weight' => 2],

        // Admin
        'filament_admin' => ['patterns' => ['app/Providers/Filament/AdminPanelProvider.php', 'app/Filament'], 'label' => 'Filament admin panel', 'weight' => 3],
        'filament_resources' => ['patterns' => ['app/Filament/Resources'], 'label' => 'Filament Resources directory', 'weight' => 2],

        // Email templates
        'email_layout' => ['patterns' => ['resources/views/emails/layouts/default.blade.php', 'resources/views/mail/layouts/default.blade.php'], 'label' => 'Email layout template', 'weight' => 2],
        'email_welcome' => ['patterns' => ['resources/views/emails/welcome', 'resources/views/mail/welcome'], 'label' => 'Welcome email template', 'weight' => 2],
        'email_password_reset' => ['patterns' => ['resources/views/emails/password-reset', 'resources/views/mail/password-reset'], 'label' => 'Password reset email', 'weight' => 2],
    ];

    public function verify(string $workspaceContainerPath): WorkspaceVerificationReport
    {
        if (! is_dir($workspaceContainerPath)) {
            return new WorkspaceVerificationReport(
                workspacePath: $workspaceContainerPath,
                workspaceExists: false,
                score: 0,
                passed: [],
                failed: ['workspace_missing' => "Workspace directory not found: {$workspaceContainerPath}"],
                warnings: [],
            );
        }

        $passed = [];
        $failed = [];
        $warnings = [];
        $weightAchieved = 0;
        $weightTotal = 0;

        // Critical checks — always run.
        foreach (self::CRITICAL_CHECKS as $key => $check) {
            $weightTotal += $check['weight'];
            if ($this->exists($workspaceContainerPath, $check)) {
                $passed[$key] = $check['label'];
                $weightAchieved += $check['weight'];
            } else {
                $failed[$key] = $check['label'].' is missing';
            }
        }

        // Backend Laravel checks — only if a `composer.json` is present
        // somewhere in the workspace (root, `backend/`, `api/`).
        $backendRoot = $this->detectBackendRoot($workspaceContainerPath);
        if ($backendRoot !== null) {
            foreach (self::BACKEND_CHECKS as $key => $check) {
                $weightTotal += $check['weight'];
                if ($this->exists($backendRoot, $check)) {
                    $passed['backend_'.$key] = '['.basename($backendRoot).'] '.$check['label'];
                    $weightAchieved += $check['weight'];
                } else {
                    $failed['backend_'.$key] = '['.basename($backendRoot).'] '.$check['label'].' is missing';
                }
            }
        } else {
            $warnings['backend_skipped'] = 'No composer.json detected — backend Laravel checks skipped';
        }

        // Frontend checks — only if a `package.json` is present.
        $frontendRoot = $this->detectFrontendRoot($workspaceContainerPath);
        if ($frontendRoot !== null) {
            foreach (self::FRONTEND_CHECKS as $key => $check) {
                $weightTotal += $check['weight'];
                if ($this->exists($frontendRoot, $check)) {
                    $passed['frontend_'.$key] = '['.basename($frontendRoot).'] '.$check['label'];
                    $weightAchieved += $check['weight'];
                } else {
                    $failed['frontend_'.$key] = '['.basename($frontendRoot).'] '.$check['label'].' is missing';
                }
            }
        } else {
            $warnings['frontend_skipped'] = 'No package.json detected — frontend checks skipped';
        }

        // DevOps checks — always run on workspace root.
        foreach (self::DEVOPS_CHECKS as $key => $check) {
            $weightTotal += $check['weight'];
            if ($this->exists($workspaceContainerPath, $check)) {
                $passed[$key] = $check['label'];
                $weightAchieved += $check['weight'];
            } else {
                $failed[$key] = $check['label'].' is missing';
            }
        }

        // Sprint 30 — Completeness checks (pages, components, emails, auth, blog, legal).
        // These run only when a backend root is detected (we look for these
        // patterns relative to the backend, since most are Blade views).
        if ($backendRoot !== null) {
            foreach (self::COMPLETENESS_CHECKS as $key => $check) {
                $weightTotal += $check['weight'];
                if ($this->existsAny($backendRoot, $check['patterns'])) {
                    $passed['completeness_'.$key] = $check['label'];
                    $weightAchieved += $check['weight'];
                } else {
                    $failed['completeness_'.$key] = $check['label'].' missing (tried: '.implode(', ', $check['patterns']).')';
                }
            }
        }

        // Bonus: warn if migrations folder is empty (likely incomplete generation).
        if ($backendRoot !== null) {
            $migrationsDir = $backendRoot.'/database/migrations';
            if (is_dir($migrationsDir)) {
                $migrations = array_diff(scandir($migrationsDir) ?: [], ['.', '..']);
                if (count($migrations) === 0) {
                    $warnings['empty_migrations'] = 'database/migrations/ exists but is empty';
                } else {
                    $passed['migrations_present'] = count($migrations).' migration file(s) detected';
                    $weightTotal += 2;
                    $weightAchieved += 2;
                }
            }
        }

        // Bonus: warn if tests/ folder is empty.
        if ($backendRoot !== null) {
            $testsDir = $backendRoot.'/tests';
            if (is_dir($testsDir)) {
                $allTests = $this->countFilesRecursive($testsDir, '.php');
                if ($allTests === 0) {
                    $warnings['empty_tests'] = 'tests/ exists but contains no .php files';
                } elseif ($allTests < 5) {
                    $warnings['few_tests'] = "Only {$allTests} test file(s) detected — consider expanding coverage";
                } else {
                    $passed['tests_present'] = "{$allTests} test file(s) detected";
                    $weightTotal += 3;
                    $weightAchieved += 3;
                }
            }
        }

        // CRITICAL_CHECKS always run, so $weightTotal is guaranteed > 0.
        $score = (int) round(($weightAchieved / $weightTotal) * 100);

        return new WorkspaceVerificationReport(
            workspacePath: $workspaceContainerPath,
            workspaceExists: true,
            score: $score,
            passed: $passed,
            failed: $failed,
            warnings: $warnings,
        );
    }

    /**
     * @param array{path: string, kind: 'file'|'dir', label: string, weight: int} $check
     */
    private function exists(string $base, array $check): bool
    {
        $target = $base.'/'.ltrim($check['path'], '/');

        return $check['kind'] === 'file' ? is_file($target) : is_dir($target);
    }

    /**
     * Sprint 30 — true if any of the provided relative patterns matches a
     * file or directory under $base. Used by COMPLETENESS_CHECKS where a
     * single concept (e.g. "Blog listing view") may legitimately live at
     * several conventional paths.
     *
     * @param list<string> $relativePaths
     */
    private function existsAny(string $base, array $relativePaths): bool
    {
        foreach ($relativePaths as $rel) {
            $target = $base.'/'.ltrim($rel, '/');
            if (is_file($target) || is_dir($target)) {
                return true;
            }
        }

        return false;
    }

    private function detectBackendRoot(string $workspace): ?string
    {
        // Conventionally: monorepo with `backend/`, or root composer.json.
        foreach (['backend', 'api', 'server', '.'] as $candidate) {
            $path = rtrim($workspace.'/'.$candidate, '/.');
            if (is_file($path.'/composer.json')) {
                return $path;
            }
        }

        return null;
    }

    private function detectFrontendRoot(string $workspace): ?string
    {
        foreach (['frontend', 'web', 'client', 'app', '.'] as $candidate) {
            $path = rtrim($workspace.'/'.$candidate, '/.');
            if (is_file($path.'/package.json')) {
                return $path;
            }
        }

        return null;
    }

    private function countFilesRecursive(string $dir, string $extension): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $count = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iter as $f) {
            if ($f instanceof SplFileInfo && $f->isFile() && str_ends_with($f->getFilename(), $extension)) {
                $count++;
            }
        }

        return $count;
    }
}
