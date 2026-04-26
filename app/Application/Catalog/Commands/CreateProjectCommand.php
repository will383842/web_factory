<?php

declare(strict_types=1);

namespace App\Application\Catalog\Commands;

use App\Application\Catalog\Handlers\CreateProjectHandler;

/**
 * Input DTO for the {@see CreateProjectHandler}.
 *
 * @phpstan-type ProjectMetadata array<string, mixed>
 */
final readonly class CreateProjectCommand
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $slug,
        public string $name,
        public ?string $description,
        public string $locale,
        public ?string $primaryDomain,
        public string $ownerId,
        public array $metadata = [],
    ) {}
}
