<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Application\Catalog\DTOs\DeploymentResult;
use App\Domain\Catalog\Entities\Project;

/**
 * Pipeline step 7 port — production deployment.
 *
 * Sprint-16 default impl is a heuristic placeholder that returns a synthetic
 * `https://{slug}.webfactory.test` URL without touching infrastructure. The
 * real Hetzner adapter (`HetznerDeploymentService`) is documented in
 * `docs/adr/0042-deployment-driver.md` — it shells out to Ansible playbooks
 * over SSH and is gated behind a feature flag for production tenants.
 */
interface DeploymentService
{
    public function deploy(Project $project): DeploymentResult;

    public function provider(): string;
}
