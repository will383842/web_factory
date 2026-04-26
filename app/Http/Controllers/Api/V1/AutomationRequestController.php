<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Marketing\Services\AutomationRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sprint 14 — public POST endpoint for the "Demande d'automatisation" CTA
 * modal. Validates RGPD opt-in, captures IP + UA + UTM, and delegates to
 * the application service which persists + emits the domain event.
 */
final class AutomationRequestController extends Controller
{
    public function __construct(private readonly AutomationRequestService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'first_name' => ['required', 'string', 'min:1', 'max:120'],
            'last_name' => ['required', 'string', 'min:1', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone_country_code' => ['required', 'string', 'max:8', 'regex:/^\+?\d{1,7}$/'],
            'phone_number' => ['required', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'rgpd_accepted' => ['required', 'accepted'],
            'source' => ['nullable', 'string', 'max:120'],
            'utm' => ['nullable', 'array'],
        ]);

        $validated['ip_address'] = $request->ip();
        $validated['user_agent'] = $request->userAgent();

        $row = $this->service->submit($validated);

        return response()->json([
            'id' => (string) $row->getKey(),
            'status' => $row->status,
            'message' => 'Demande reçue. Nous vous recontactons sous 24 h ouvrées.',
        ], 201);
    }
}
