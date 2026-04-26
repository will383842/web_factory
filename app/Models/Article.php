<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Article extends Model
{
    protected $fillable = [
        'project_id', 'slug', 'locale', 'title', 'excerpt', 'body',
        'featured_image_url', 'seo_keywords', 'is_pillar', 'status',
        'published_at', 'word_count', 'reading_time_minutes', 'quality_score',
    ];

    protected $casts = [
        'is_pillar' => 'bool',
        'published_at' => 'datetime',
        'seo_keywords' => AsArrayObject::class,
        'word_count' => 'integer',
        'reading_time_minutes' => 'integer',
        'quality_score' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
