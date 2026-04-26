<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $slug
 * @property string $name
 * @property int $price_cents
 * @property string $currency
 * @property string $billing_cycle
 * @property bool $is_active
 */
final class BillingPlan extends Model
{
    public const CYCLE_MONTHLY = 'monthly';

    public const CYCLE_YEARLY = 'yearly';

    public const CYCLE_ONE_TIME = 'one_time';

    protected $table = 'billing_plans';

    protected $fillable = [
        'project_id', 'slug', 'name', 'description',
        'price_cents', 'currency', 'billing_cycle',
        'features', 'is_active',
        'stripe_product_id', 'stripe_price_id',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'is_active' => 'boolean',
        'features' => AsArrayObject::class,
    ];

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<BillingSubscription, self> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BillingSubscription::class, 'plan_id');
    }
}
