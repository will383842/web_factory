<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $code
 * @property bool $is_active
 * @property int $redeemed_count
 * @property int $bonus_credits_cents
 */
final class ReferralCode extends Model
{
    protected $table = 'referral_codes';

    protected $fillable = ['owner_id', 'code', 'is_active', 'redeemed_count', 'bonus_credits_cents'];

    protected $casts = [
        'is_active' => 'boolean',
        'redeemed_count' => 'integer',
        'bonus_credits_cents' => 'integer',
    ];

    /** @return BelongsTo<User, self> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
