<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $status
 */
final class AutomationRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    protected $table = 'automation_requests';

    protected $fillable = [
        'project_id',
        'first_name', 'last_name', 'email',
        'phone_country_code', 'phone_number',
        'company', 'category', 'message',
        'rgpd_accepted', 'status',
        'ip_address', 'user_agent', 'source', 'utm',
        'contacted_at',
    ];

    protected $casts = [
        'rgpd_accepted' => 'boolean',
        'utm' => AsArrayObject::class,
        'contacted_at' => 'datetime',
    ];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function fullPhone(): string
    {
        return $this->phone_country_code.' '.$this->phone_number;
    }

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
