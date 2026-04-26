<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $code
 * @property int|null $percent_off
 * @property int|null $amount_off
 * @property string|null $currency
 * @property string $duration
 * @property bool $is_active
 * @property int $redeemed_count
 * @property int|null $max_redemptions
 * @property Carbon|null $expires_at
 */
final class BillingCoupon extends Model
{
    public const DURATION_ONCE = 'once';

    public const DURATION_REPEATING = 'repeating';

    public const DURATION_FOREVER = 'forever';

    protected $table = 'billing_coupons';

    protected $fillable = [
        'project_id', 'code', 'name',
        'percent_off', 'amount_off', 'currency',
        'duration', 'duration_in_months', 'max_redemptions', 'redeemed_count',
        'expires_at', 'is_active', 'stripe_coupon_id',
    ];

    protected $casts = [
        'percent_off' => 'integer',
        'amount_off' => 'integer',
        'duration_in_months' => 'integer',
        'max_redemptions' => 'integer',
        'redeemed_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
