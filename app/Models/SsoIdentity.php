<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property string|null $email
 * @property string|null $access_token_encrypted
 * @property string|null $refresh_token_encrypted
 * @property Carbon|null $expires_at
 */
final class SsoIdentity extends Model
{
    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_MICROSOFT = 'microsoft';

    public const PROVIDER_APPLE = 'apple';

    public const PROVIDER_OKTA = 'okta';

    public const PROVIDER_GITHUB = 'github';

    protected $table = 'sso_identities';

    protected $fillable = [
        'user_id', 'provider', 'provider_user_id', 'email',
        'access_token_encrypted', 'refresh_token_encrypted',
        'expires_at', 'raw_payload',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'raw_payload' => AsArrayObject::class,
        'access_token_encrypted' => 'encrypted',
        'refresh_token_encrypted' => 'encrypted',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
