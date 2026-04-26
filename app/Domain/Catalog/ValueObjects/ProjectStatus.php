<?php

declare(strict_types=1);

namespace App\Domain\Catalog\ValueObjects;

use App\Domain\Catalog\Entities\Project;

/**
 * Lifecycle states of a {@see Project} along the
 * 7-step WebFactory pipeline (see Spec 00 — VISION_PIPELINE).
 *
 *  draft        : just submitted, not yet analyzed
 *  analyzing    : step 1 — idea analysis + virality + value scoring
 *  blueprinting : step 2 — pages, parcours, KPI + HTML simulation
 *  designing    : step 3 — design system + 8 mockups
 *  building     : step 4-5 — ZIP brief + code generation via Claude Code
 *  deployed     : step 6-7 — content + Hetzner deploy + GSC/Bing/IndexNow
 *  archived     : terminal state for cancelled or retired projects
 */
enum ProjectStatus: string
{
    case Draft = 'draft';
    case Analyzing = 'analyzing';
    case Blueprinting = 'blueprinting';
    case Designing = 'designing';
    case Building = 'building';
    case Deployed = 'deployed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Analyzing => 'Analyzing',
            self::Blueprinting => 'Blueprinting',
            self::Designing => 'Designing',
            self::Building => 'Building',
            self::Deployed => 'Deployed',
            self::Archived => 'Archived',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Deployed || $this === self::Archived;
    }
}
