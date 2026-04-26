<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\ContentBundle;
use App\Domain\Catalog\Entities\Project;

/**
 * Pipeline step 6 port — multilingual content production from blueprint.
 *
 * Sprint-15 default impl is a heuristic adapter that produces one Page per
 * blueprint page entry, one pillar Article per blueprint journey, and three
 * FAQ entries per project — for each locale in the project's audience set.
 *
 * Sprint 19 swaps the heuristic adapter for a real Claude-backed adapter.
 *
 * @param list<string> $locales e.g., ['fr-FR', 'en-US']
 */
interface ContentProductionService
{
    /**
     * @param list<string> $locales
     */
    public function produce(Project $project, array $locales): ContentBundle;
}
