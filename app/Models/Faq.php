<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Faq extends Model
{
    protected $fillable = [
        'project_id', 'locale', 'question', 'answer', 'category',
        'is_featured', 'view_count', 'helpful_count', 'status',
    ];

    protected $casts = [
        'is_featured' => 'bool',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
