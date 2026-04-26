<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $email
 * @property string|null $stripe_customer_id
 */
final class BillingCustomer extends Model
{
    protected $table = 'billing_customers';

    protected $fillable = [
        'project_id', 'user_id', 'email', 'name',
        'stripe_customer_id', 'default_payment_method',
        'billing_address', 'metadata',
    ];

    protected $casts = [
        'billing_address' => AsArrayObject::class,
        'metadata' => AsArrayObject::class,
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

    /** @return HasMany<BillingSubscription, self> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BillingSubscription::class, 'customer_id');
    }

    /** @return HasMany<BillingInvoice, self> */
    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'customer_id');
    }
}
