<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Domain\Marketing\Events\AutomationRequested;
use App\Domain\Shared\Contracts\EventDispatcher;
use App\Models\AutomationRequest;

/**
 * Sprint 14 — turns the validated POST payload from the public CTA modal
 * into a persisted {@see AutomationRequest} + an {@see AutomationRequested}
 * domain event so the notification dispatcher (Sprint 13.4) can fan-out.
 */
final class AutomationRequestService
{
    public function __construct(private readonly EventDispatcher $events) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function submit(array $payload): AutomationRequest
    {
        /** @var AutomationRequest $row */
        $row = AutomationRequest::query()->create([
            'project_id' => $payload['project_id'] ?? null,
            'first_name' => (string) $payload['first_name'],
            'last_name' => (string) $payload['last_name'],
            'email' => (string) $payload['email'],
            'phone_country_code' => (string) $payload['phone_country_code'],
            'phone_number' => (string) $payload['phone_number'],
            'company' => $payload['company'] ?? null,
            'category' => (string) $payload['category'],
            'message' => (string) $payload['message'],
            'rgpd_accepted' => (bool) ($payload['rgpd_accepted'] ?? false),
            'status' => AutomationRequest::STATUS_NEW,
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => isset($payload['user_agent']) ? mb_substr((string) $payload['user_agent'], 0, 500) : null,
            'source' => $payload['source'] ?? null,
            'utm' => $payload['utm'] ?? null,
        ]);

        $this->events->dispatch(new AutomationRequested(
            automationRequestId: (int) $row->getKey(),
            projectId: $row->project_id,
            email: $row->email,
            category: $row->category,
        ));

        return $row;
    }
}
