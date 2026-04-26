<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $event_type
 * @property string $channel
 * @property string $locale
 * @property string|null $subject
 * @property string $body
 * @property bool $is_active
 */
final class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'project_id', 'event_type', 'channel', 'locale',
        'subject', 'body', 'payload_schema', 'is_active',
    ];

    protected $casts = [
        'payload_schema' => AsArrayObject::class,
        'is_active' => 'boolean',
    ];

    /**
     * Render the template body by substituting `{{ key }}` placeholders.
     *
     * @param array<string, scalar|null> $payload
     */
    public function render(array $payload): string
    {
        $body = $this->body;
        foreach ($payload as $key => $value) {
            $body = str_replace('{{ '.$key.' }}', (string) $value, $body);
        }

        return $body;
    }

    /** @return BelongsTo<Project, self> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
