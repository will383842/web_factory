<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $name
 * @property Carbon $occurred_at
 */
final class EventTracking extends Model
{
    public const NAME_PAGE_VIEW = 'page_view';

    public const NAME_CTA_CLICK = 'cta_click';

    public const NAME_SIGNUP = 'signup';

    public const NAME_PURCHASE = 'purchase';

    protected $table = 'event_tracking';

    protected $fillable = [
        'project_id', 'user_id', 'session_id', 'name',
        'properties', 'source', 'ip_address', 'occurred_at',
    ];

    protected $casts = [
        'properties' => AsArrayObject::class,
        'occurred_at' => 'datetime',
    ];

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
