<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\IdeaAnalysisResult;
use App\Domain\Catalog\Entities\Project;

/**
 * Port for pipeline step 1.
 *
 * Sprint 5 default impl lives in App\Infrastructure\Pipeline (avoid the
 * direct `use` import here — Application must not depend on Infrastructure
 * per ADR 0008 hexagonal ports & adapters).
 * Sprint 19 will swap in a Claude-API powered adapter.
 */
interface IdeaAnalysisService
{
    public function analyze(Project $project): IdeaAnalysisResult;
}
