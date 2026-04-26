<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\IdeaAnalysisResult;
use App\Domain\Catalog\Entities\Project;
use App\Infrastructure\Pipeline\HeuristicIdeaAnalysisService;

/**
 * Port for pipeline step 1.
 *
 * Sprint 5 default impl: {@see HeuristicIdeaAnalysisService}.
 * Sprint 19 will swap in a Claude-API powered adapter.
 */
interface IdeaAnalysisService
{
    public function analyze(Project $project): IdeaAnalysisResult;
}
