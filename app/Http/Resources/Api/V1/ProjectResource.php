<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
final class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'locale' => $this->locale,
            'primary_domain' => $this->primary_domain,
            'virality_score' => $this->virality_score,
            'value_score' => $this->value_score,
            'owner_id' => (string) $this->owner_id,
            'metadata' => (array) $this->metadata,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
