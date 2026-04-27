<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Sprint 18 — Observability healthcheck.
 *
 * `/api/v1/health` returns 200 + per-dependency JSON when everything is up,
 * 503 + the failing component when any check fails. Uptime probes (Hetzner
 * load-balancer, BetterStack, Pingdom) consume this endpoint.
 */
final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => ['status' => 'ok', 'version' => config('app.name').' '.app()->version()],
            'db' => $this->probeDatabase(),
            'redis' => $this->probeRedis(),
        ];

        $isHealthy = collect($checks)->every(fn (array $c): bool => ($c['status'] ?? '') === 'ok');

        return response()->json([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => now()->toAtomString(),
        ], $isHealthy ? 200 : 503);
    }

    /**
     * @return array<string, string>
     */
    private function probeDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'down', 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, string>
     */
    private function probeRedis(): array
    {
        try {
            Redis::ping();

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'down', 'error' => $e->getMessage()];
        }
    }
}
