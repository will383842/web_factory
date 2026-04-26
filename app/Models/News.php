<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'project_id', 'slug', 'locale', 'title', 'summary', 'body',
        'source_url', 'category', 'status', 'published_at', 'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
