<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\BriefBundle;
use App\Application\Catalog\DTOs\BriefScore;
use App\Domain\Catalog\Entities\Project;

/**
 * Pipeline step 4b port — scores a brief on completeness/coherence.
 *
 * The pipeline gate (see GitHubRepoInitJob) only proceeds if score >= 85.
 */
interface BriefScorerService
{
    public function score(Project $project, BriefBundle $bundle): BriefScore;
}
