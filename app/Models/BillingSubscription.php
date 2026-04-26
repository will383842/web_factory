<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $customer_id
 * @property int $plan_id
 * @property string $status
 * @property Carbon|null $current_period_end
 */
final class BillingSubscription extends Model
{
    public const STATUS_TRIALING = 'trialing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';

    public const ACTIVE_STATUSES = [self::STATUS_TRIALING, self::STATUS_ACTIVE];

    protected $table = 'billing_subscriptions';

    protected $fillable = [
        'project_id', 'customer_id', 'plan_id', 'status',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'canceled_at', 'ended_at', 'cancel_at_period_end',
        'stripe_subscription_id', 'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => AsArrayObject::class,
    ];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<BillingCustomer, self> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(BillingCustomer::class, 'customer_id');
    }

    /** @return BelongsTo<BillingPlan, self> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'plan_id');
    }

    /** @return HasMany<BillingInvoice, self> */
    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'subscription_id');
    }
}
