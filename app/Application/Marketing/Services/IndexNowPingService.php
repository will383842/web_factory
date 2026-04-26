<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

/**
 * Port for the IndexNow protocol (Bing / Yandex / Naver / Seznam).
 *
 * Sprint-8 default impl is a logger-only mock that records the URLs that
 * would have been pinged. Sprint 16 (Deployment) will wire the real HTTP
 * adapter against api.indexnow.org with the project-specific key file.
 */
interface IndexNowPingService
{
    /**
     * @param list<string> $urls
     *
     * @return array{accepted: int, host: string}
     */
    public function ping(string $host, string $key, array $urls): array;
}
