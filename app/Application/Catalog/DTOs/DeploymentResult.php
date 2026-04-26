<?php

declare(strict_types=1);

namespace App\Application\Catalog\DTOs;

/**
 * Output of pipeline step 7 — production deployment.
 *
 * `liveUrl` is the customer-facing URL of the deployed site. `provider`
 * identifies the deployment target (hetzner / cloudflare-pages / vercel).
 */
final readonly class DeploymentResult
{
    public function __construct(
        public bool $success,
        public string $provider,
        public ?string $liveUrl = null,
        public ?string $previewUrl = null,
        public ?string $deploymentId = null,
        public ?string $errorMessage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toMetadataArray(): array
    {
        return [
            'success' => $this->success,
            'provider' => $this->provider,
            'live_url' => $this->liveUrl,
            'preview_url' => $this->previewUrl,
            'deployment_id' => $this->deploymentId,
            'error' => $this->errorMessage,
        ];
    }
}
