<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-tenant request scope.
 *
 * Resolves the current `project_id` from either:
 *   1. an explicit `X-Project-Id` header
 *   2. the route binding `{project}` parameter (Catalog\Project)
 *   3. the authenticated user's first owned project (fallback)
 *
 * The resolved id is stored on the container as `tenant.project_id` so
 * services lower down the stack (KB search, sitemap builder, etc.) can
 * scope automatically. Every request is logged with this tag for audit.
 *
 * Cross-tenant attempts are not blocked here (controllers do that with
 * explicit policies); the middleware only TAGS the request so audit
 * trails stay consistent.
 */
final class TenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $this->resolveProjectId($request);

        if ($projectId !== null) {
            app()->instance('tenant.project_id', (string) $projectId);
            $request->attributes->set('tenant.project_id', (string) $projectId);

            Log::withContext(['tenant_project_id' => (string) $projectId]);
        }

        return $next($request);
    }

    private function resolveProjectId(Request $request): ?int
    {
        $headerValue = $request->header('X-Project-Id');
        $headerProjectId = is_string($headerValue) && ctype_digit($headerValue) ? (int) $headerValue : null;
        if ($headerProjectId !== null) {
            return $headerProjectId;
        }

        $routeProject = $request->route('project');
        if (is_object($routeProject) && method_exists($routeProject, 'getKey')) {
            $key = $routeProject->getKey();
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                return (int) $key;
            }
        }

        $user = $request->user();
        if ($user !== null) {
            // Eloquent User has a hasMany owned projects via owner_id; use a
            // light query to avoid eager-loading every relation.
            /** @var int|null $first */
            $first = Project::query()
                ->where('owner_id', $user->getKey())
                ->orderBy('id')
                ->value('id');

            return $first !== null ? (int) $first : null;
        }

        return null;
    }
}
