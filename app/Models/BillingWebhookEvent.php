<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string $event_id
 * @property string $event_type
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
final class BillingWebhookEvent extends Model
{
    public const PROVIDER_STRIPE = 'stripe';

    public const PROVIDER_PADDLE = 'paddle';

    public const PROVIDER_LEMONSQUEEZY = 'lemonsqueezy';

    public const PROVIDER_MOLLIE = 'mollie';

    protected $table = 'billing_webhook_events';

    protected $fillable = [
        'provider', 'event_id', 'event_type', 'payload',
        'received_at', 'processed_at', 'processing_error',
    ];

    protected $casts = [
        'payload' => AsArrayObject::class,
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function isProcessed(): bool
    {
        return $this->processed_at !== null && $this->processing_error === null;
    }
}
