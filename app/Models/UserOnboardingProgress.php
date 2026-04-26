<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $flow_id
 * @property ArrayObject<int, string> $completed_steps
 * @property int $score
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
final class UserOnboardingProgress extends Model
{
    protected $table = 'user_onboarding_progress';

    protected $fillable = [
        'user_id', 'flow_id', 'completed_steps', 'score',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'completed_steps' => AsArrayObject::class,
        'score' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<OnboardingFlow, self> */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(OnboardingFlow::class, 'flow_id');
    }
}
