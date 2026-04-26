<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketing;

use App\Application\Marketing\Services\IndexNowPingService;
use Illuminate\Support\Facades\Log;

/**
 * Sprint-8 logger-only adapter — records pinged URLs without hitting the
 * real IndexNow endpoint. Swap to {@see HttpIndexNowPingService} in
 * Sprint 16 (Deployment).
 */
final class LogIndexNowPingService implements IndexNowPingService
{
    public function ping(string $host, string $key, array $urls): array
    {
        Log::channel('stack')->info('IndexNow ping (mock)', [
            'host' => $host,
            'key' => substr($key, 0, 4).'…',
            'url_count' => count($urls),
            'sample' => array_slice($urls, 0, 3),
        ]);

        return ['accepted' => count($urls), 'host' => $host];
    }
}
