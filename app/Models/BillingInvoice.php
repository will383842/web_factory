<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $customer_id
 * @property int|null $subscription_id
 * @property int $amount_cents
 * @property string $currency
 * @property string $status
 */
final class BillingInvoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_PAID = 'paid';

    public const STATUS_UNCOLLECTIBLE = 'uncollectible';

    public const STATUS_VOID = 'void';

    protected $table = 'billing_invoices';

    protected $fillable = [
        'project_id', 'customer_id', 'subscription_id',
        'number', 'amount_cents', 'amount_paid_cents', 'currency',
        'status', 'paid_at', 'due_at',
        'stripe_invoice_id', 'pdf_url', 'line_items',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'amount_paid_cents' => 'integer',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
        'line_items' => AsArrayObject::class,
    ];

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

    /** @return BelongsTo<BillingSubscription, self> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BillingSubscription::class, 'subscription_id');
    }
}
