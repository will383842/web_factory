<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $role
 * @property Carbon|null $joined_at
 */
final class TeamMember extends Model
{
    protected $table = 'team_members';

    protected $fillable = ['team_id', 'user_id', 'role', 'joined_at'];

    protected $casts = ['joined_at' => 'datetime'];

    /** @return BelongsTo<Team, self> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
