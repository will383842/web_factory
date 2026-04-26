<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $template_id
 * @property string $channel
 * @property string $event_type
 * @property string $recipient
 * @property string $status
 * @property string|null $external_id
 * @property string|null $error_message
 * @property Carbon|null $sent_at
 */
final class NotificationDispatch extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'notification_dispatches';

    protected $fillable = [
        'user_id', 'template_id', 'channel', 'event_type', 'recipient',
        'payload', 'status', 'external_id', 'error_message',
        'sent_at', 'delivered_at',
    ];

    protected $casts = [
        'payload' => AsArrayObject::class,
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<NotificationTemplate, self> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }
}
