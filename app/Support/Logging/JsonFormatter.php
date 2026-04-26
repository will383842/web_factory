<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\LogRecord;

/**
 * Project-wide JSON log formatter.
 *
 * Adds standard fields (`service`, `bc`, `trace_id`) on every log line so logs
 * stay correlatable across bounded contexts when piped to a centralized stack
 * (Sentry breadcrumbs, ELK, Loki, ...).
 */
final class JsonFormatter extends MonologJsonFormatter
{
    public function __construct()
    {
        parent::__construct(self::BATCH_MODE_NEWLINES, true, false, true);
    }

    public function format(LogRecord $record): string
    {
        $extra = $record->extra;

        $extra['service'] ??= config('app.name', 'webfactory');
        $extra['env'] ??= config('app.env', 'unknown');
        $extra['bc'] ??= $record->context['bc'] ?? null;
        $extra['trace_id'] ??= $record->context['trace_id']
            ?? $record->extra['trace_id']
            ?? request()?->header('X-Request-Id');

        $record = $record->with(extra: array_filter($extra, static fn ($v) => $v !== null));

        return parent::format($record);
    }
}
