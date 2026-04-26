<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent persistence model for the Catalog\Project aggregate.
 *
 * Lives under App\Models per Laravel + Filament convention; the ArchTest
 * already whitelists `App\Models` as a valid Eloquent location alongside
 * `App\Infrastructure\Persistence\Eloquent`.
 */
final class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'status',
        'locale',
        'primary_domain',
        'virality_score',
        'value_score',
        'owner_id',
        'metadata',
    ];

    protected $casts = [
        'virality_score' => 'integer',
        'value_score' => 'integer',
        'metadata' => AsArrayObject::class,
    ];

    /**
     * @return BelongsTo<User, self>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
