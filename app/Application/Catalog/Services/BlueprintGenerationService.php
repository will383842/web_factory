<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\Blueprint;
use App\Domain\Catalog\Entities\Project;

interface BlueprintGenerationService
{
    public function generate(Project $project): Blueprint;
}
