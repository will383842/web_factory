<?php

declare(strict_types=1);

namespace App\Infrastructure\Pipeline;

use App\Application\Catalog\DTOs\DeploymentResult;
use App\Application\Catalog\Services\DeploymentService;
use App\Domain\Catalog\Entities\Project;
use Illuminate\Support\Str;

/**
 * Sprint-16 placeholder for pipeline step 7 (production deploy).
 *
 * Returns a synthetic `https://{slug}.webfactory.test` URL so the rest of the
 * stack (status transition, IndexNow listener, observability annotations) can
 * be wired and tested without touching real infrastructure.
 *
 * Real Hetzner adapter `HetznerDeploymentService` lives in `docs/adr/0042-*`
 * and is wired via env flag at deploy time of WebFactory itself.
 */
final class PlaceholderDeploymentService implements DeploymentService
{
    public function provider(): string
    {
        return 'placeholder';
    }

    public function deploy(Project $project): DeploymentResult
    {
        return new DeploymentResult(
            success: true,
            provider: $this->provider(),
            liveUrl: 'https://'.$project->slug.'.webfactory.test',
            previewUrl: 'https://preview-'.Str::lower(Str::random(8)).'.webfactory.test',
            deploymentId: 'dep_'.Str::lower(Str::random(16)),
        );
    }
}
