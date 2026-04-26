<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $invited_by
 * @property string $email
 * @property string $role
 * @property string $token_hash
 * @property string $status
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property int|null $accepted_by
 */
final class TeamInvitation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'team_invitations';

    protected $fillable = [
        'team_id', 'invited_by', 'email', 'role',
        'token_hash', 'status', 'expires_at',
        'accepted_at', 'accepted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expires_at->isFuture();
    }

    /** @return BelongsTo<Team, self> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, self> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
