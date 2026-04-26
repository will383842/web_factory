<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $slug
 * @property string $name
 * @property string $audience
 * @property ArrayObject<int, array{key:string, title:string, weight?:int, cta_url?:string, icon?:string}> $steps
 * @property bool $is_active
 */
final class OnboardingFlow extends Model
{
    public const AUDIENCE_USER = 'user';

    public const AUDIENCE_ADMIN = 'admin';

    public const AUDIENCE_TEAM_OWNER = 'team_owner';

    protected $table = 'onboarding_flows';

    protected $fillable = ['project_id', 'slug', 'name', 'audience', 'steps', 'is_active'];

    protected $casts = [
        'steps' => AsArrayObject::class,
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<UserOnboardingProgress, self> */
    public function progress(): HasMany
    {
        return $this->hasMany(UserOnboardingProgress::class, 'flow_id');
    }
}
