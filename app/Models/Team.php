<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $owner_id
 * @property int|null $project_id
 * @property string $slug
 * @property string $name
 * @property string|null $logo_url
 */
final class Team extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    protected $table = 'teams';

    protected $fillable = ['owner_id', 'project_id', 'slug', 'name', 'logo_url', 'settings'];

    protected $casts = ['settings' => AsArrayObject::class];

    /** @return BelongsTo<User, self> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<TeamMember, self> */
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** @return BelongsToMany<User, self> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<TeamInvitation, self> */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }
}
