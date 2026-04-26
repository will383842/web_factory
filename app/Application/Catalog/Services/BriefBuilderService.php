<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\BriefBundle;
use App\Domain\Catalog\Entities\Project;

/**
 * Pipeline step 4 port — builds the 35-file brief feeding code generation.
 */
interface BriefBuilderService
{
    public function build(Project $project): BriefBundle;
}
