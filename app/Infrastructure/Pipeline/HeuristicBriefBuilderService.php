<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\BriefBundle;
use App\Application\Catalog\Services\BriefBuilderService;
use App\Domain\Catalog\Entities\Project;

/**
 * Sprint-6 deterministic placeholder for pipeline step 4 — assembles a
 * 35-file brief from the project metadata produced by steps 1-3.
 *
 * File map (35 entries):
 *  - README.md
 *  - blueprint.json + design/tokens.json + design/mockups.json
 *  - pages/{slug}.md          (10 — one per blueprint page)
 *  - mockups/{name}.html      (8  — one per design mockup)
 *  - .env.example
 *  - composer.json.tpl
 *  - vite.config.ts.tpl
 *  - tailwind.config.ts.tpl
 *  - docker-compose.yml.tpl
 *  - 10 misc instruction files (architecture.md, seo.md, deploy.md ...)
 *
 * Sprint 19 will swap to a Claude-powered builder that generates real
 * content for each file. Today's content is structured stubs.
 */
final class HeuristicBriefBuilderService implements BriefBuilderService
{
    private const MISC_INSTRUCTIONS = [
        'docs/architecture.md',
        'docs/seo.md',
        'docs/deploy.md',
        'docs/i18n.md',
        'docs/security.md',
        'docs/perf.md',
        'docs/accessibility.md',
        'docs/observability.md',
        'docs/launch.md',
        'docs/qa.md',
    ];

    public function build(Project $project): BriefBundle
    {
        /** @var array<string, mixed> $metadata */
        $metadata = $project->metadata;

        /** @var array<string, mixed> $analysis */
        $analysis = (array) ($metadata['analysis'] ?? []);
        /** @var array<string, mixed> $blueprint */
        $blueprint = (array) ($metadata['blueprint'] ?? []);
        /** @var array<string, mixed> $design */
        $design = (array) ($metadata['design'] ?? []);

        /** @var list<array{slug: string, title: string, type: string}> $pages */
        $pages = (array) ($blueprint['pages'] ?? []);
        /** @var list<string> $mockupNames */
        $mockupNames = (array) ($design['mockups_summary'] ?? []);

        $files = [];

        $files['README.md'] = sprintf(
            "# %s\n\n%s\n\n- Slug: `%s`\n- Locale: `%s`\n- Virality: %d/100\n- Value: %d/100\n",
            $project->name,
            $project->description ?? '',
            $project->slug->value,
            $project->locale->value,
            $project->viralityScore,
            $project->valueScore,
        );

        $files['blueprint.json'] = (string) json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $files['design/tokens.json'] = (string) json_encode($design['tokens'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $files['design/mockups.json'] = (string) json_encode($mockupNames, JSON_PRETTY_PRINT);
        $files['analysis.json'] = (string) json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        foreach ($pages as $page) {
            $slugSafe = str_replace('/', '_', $page['slug']);
            $files["pages/{$slugSafe}.md"] = sprintf(
                "# %s\n\nType: %s\nSlug: %s\n\n_TODO: copy + SEO + JSON-LD_\n",
                $page['title'],
                $page['type'],
                $page['slug'],
            );
        }

        foreach ($mockupNames as $mockupName) {
            $files["mockups/{$mockupName}.html"] = sprintf(
                "<!-- mockup placeholder for %s in project %s -->\n",
                $mockupName,
                $project->slug->value,
            );
        }

        $files['.env.example'] = "APP_NAME={$project->name}\nAPP_LOCALE={$project->locale->value}\n";
        $files['composer.json.tpl'] = '{"name":"webfactory/'.$project->slug->value.'"}';
        $files['vite.config.ts.tpl'] = "// Vite config placeholder for {$project->slug->value}\n";
        $files['tailwind.config.ts.tpl'] = "// Tailwind config placeholder\n";
        $files['docker-compose.yml.tpl'] = "# docker-compose.yml placeholder\n";

        foreach (self::MISC_INSTRUCTIONS as $path) {
            $files[$path] = "# {$path}\n\n_TODO: fill content based on the project brief._\n";
        }

        ksort($files);
        $checksum = sha1((string) json_encode(array_keys($files)).serialize($files));

        return new BriefBundle(files: $files, checksum: $checksum);
    }
}
