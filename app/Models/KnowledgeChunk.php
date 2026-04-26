<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistence model for the AI BC's KnowledgeChunk aggregate.
 *
 * Note: the `embedding` column is a Postgres `vector(384)` (pgvector) and is
 * read/written as raw text by Eloquent. Use the dedicated repository
 * methods to (de)serialize the float vector — never set this attribute
 * directly.
 */
final class KnowledgeChunk extends Model
{
    protected $fillable = [
        'project_id', 'source_type', 'source_id', 'source_url',
        'topic', 'locale', 'content', 'content_tokens',
    ];

    protected $casts = [
        'content_tokens' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
