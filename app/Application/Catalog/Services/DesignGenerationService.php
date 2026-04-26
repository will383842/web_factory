<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\Blueprint;
use App\Application\Catalog\DTOs\DesignSystem;
use App\Domain\Catalog\Entities\Project;

interface DesignGenerationService
{
    public function generate(Project $project, Blueprint $blueprint): DesignSystem;
}
