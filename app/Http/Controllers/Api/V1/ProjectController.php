<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Catalog\Commands\CreateProjectCommand;
use App\Application\Catalog\Handlers\CreateProjectHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource as ApiProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * REST API for Catalog\Project (Spec 30 — Sprint 4).
 *
 * Auth: Sanctum personal access tokens (`Authorization: Bearer <token>`).
 * Scope: an authenticated user can list/show their own projects, plus admins
 * see all (Filament panel role gate covers admin moves; the API uses simple
 * ownership filter for now — full policies land Sprint 8).
 */
final class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()->latest('id');

        // Non-admins see their own only.
        $user = $request->user();
        if ($user !== null && ! $user->hasRole('admin')) {
            $query->where('owner_id', $user->getKey());
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', '25');
        $perPage = min(max($perPage, 1), 100);

        return ApiProjectResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Project $project): ApiProjectResource
    {
        $this->authorizeView($request, $project);

        return new ApiProjectResource($project);
    }

    public function store(StoreProjectRequest $request, CreateProjectHandler $handler): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $command = new CreateProjectCommand(
            slug: (string) $request->validated('slug'),
            name: (string) $request->validated('name'),
            description: $request->validated('description'),
            locale: (string) $request->validated('locale'),
            primaryDomain: $request->validated('primary_domain'),
            ownerId: (string) $user->getKey(),
            metadata: (array) $request->validated('metadata', []),
        );

        $project = $handler->handle($command);

        $model = Project::query()->find($project->id);

        return (new ApiProjectResource($model))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeView($request, $project);
        $project->delete();

        return response()->json(null, 204);
    }

    private function authorizeView(Request $request, Project $project): void
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }
        if ($user->hasRole('admin')) {
            return;
        }
        if ((int) $project->owner_id !== (int) $user->getKey()) {
            abort(403);
        }
    }
}
