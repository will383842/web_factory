<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Page extends Model
{
    protected $fillable = [
        'project_id', 'slug', 'locale', 'title', 'type', 'status',
        'published_at', 'content_blocks', 'meta_tags',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'content_blocks' => AsArrayObject::class,
        'meta_tags' => AsArrayObject::class,
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
