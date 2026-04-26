<?php

declare(strict_types=1);

namespace App\Application\Communication\Services;

use App\Application\Communication\DTOs\NotificationMessage;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;

/**
 * Sprint 13.4 — orchestrates notification sending.
 *
 *  1) Look up the active template for (project, event_type, channel, locale)
 *  2) Check the user's preference (skip if explicitly disabled)
 *  3) Render the template body with the payload
 *  4) Delegate to the channel adapter and persist a NotificationDispatch row
 *
 * Transactional event types defined in `TRANSACTIONAL_EVENTS` bypass the
 * preference check (RGPD-compliant — security alerts and password resets are
 * legally required regardless of marketing opt-out).
 */
final class NotificationDispatcher
{
    /**
     * Event types that bypass the preference matrix (always sent).
     */
    public const TRANSACTIONAL_EVENTS = [
        'auth.password_reset',
        'auth.email_verification',
        'security.login_anomaly',
        'billing.payment_failed',
        'billing.invoice_paid',
    ];

    public function __construct(private readonly NotificationChannelRegistry $registry) {}

    /**
     * @param array<string, scalar|null> $payload
     */
    public function dispatch(
        User $user,
        string $eventType,
        string $channel,
        string $recipient,
        array $payload = [],
        ?int $projectId = null,
        string $locale = 'en',
    ): NotificationDispatch {
        // 1. Preference check (skipped for transactional events)
        if (! $this->isTransactional($eventType) && $this->userHasOptedOut($user, $channel, $eventType)) {
            return $this->logDispatch(
                user: $user,
                template: null,
                channel: $channel,
                eventType: $eventType,
                recipient: $recipient,
                payload: $payload,
                status: NotificationDispatch::STATUS_SKIPPED,
                error: 'User opted out',
            );
        }

        // 2. Template lookup (project-scoped first, then platform default)
        $template = $this->resolveTemplate($projectId, $eventType, $channel, $locale);
        if ($template === null) {
            return $this->logDispatch(
                user: $user,
                template: null,
                channel: $channel,
                eventType: $eventType,
                recipient: $recipient,
                payload: $payload,
                status: NotificationDispatch::STATUS_FAILED,
                error: 'No active template',
            );
        }

        // 3. Render + send
        $body = $template->render($payload);
        $message = new NotificationMessage(
            channel: $channel,
            eventType: $eventType,
            recipient: $recipient,
            body: $body,
            subject: $template->subject,
            payload: $payload,
            userId: $user->getKey(),
            templateId: $template->getKey(),
        );

        $result = $this->registry->get($channel)->send($message);

        return $this->logDispatch(
            user: $user,
            template: $template,
            channel: $channel,
            eventType: $eventType,
            recipient: $recipient,
            payload: $payload,
            status: $result->success ? NotificationDispatch::STATUS_SENT : NotificationDispatch::STATUS_FAILED,
            externalId: $result->externalId,
            error: $result->errorMessage,
        );
    }

    private function isTransactional(string $eventType): bool
    {
        return in_array($eventType, self::TRANSACTIONAL_EVENTS, true);
    }

    private function userHasOptedOut(User $user, string $channel, string $eventType): bool
    {
        $pref = NotificationPreference::query()
            ->where('user_id', $user->getKey())
            ->where('channel', $channel)
            ->where('event_type', $eventType)
            ->first();

        return $pref !== null && ! $pref->enabled;
    }

    private function resolveTemplate(?int $projectId, string $eventType, string $channel, string $locale): ?NotificationTemplate
    {
        $base = NotificationTemplate::query()
            ->where('event_type', $eventType)
            ->where('channel', $channel)
            ->where('is_active', true);

        if ($projectId !== null) {
            $scoped = (clone $base)
                ->where('project_id', $projectId)
                ->where('locale', $locale)
                ->first();
            if ($scoped !== null) {
                return $scoped;
            }
        }

        return (clone $base)
            ->whereNull('project_id')
            ->where('locale', $locale)
            ->first();
    }

    /**
     * @param array<string, scalar|null> $payload
     */
    private function logDispatch(
        User $user,
        ?NotificationTemplate $template,
        string $channel,
        string $eventType,
        string $recipient,
        array $payload,
        string $status,
        ?string $externalId = null,
        ?string $error = null,
    ): NotificationDispatch {
        /** @var NotificationDispatch $row */
        $row = NotificationDispatch::query()->create([
            'user_id' => $user->getKey(),
            'template_id' => $template?->getKey(),
            'channel' => $channel,
            'event_type' => $eventType,
            'recipient' => $recipient,
            'payload' => $payload,
            'status' => $status,
            'external_id' => $externalId,
            'error_message' => $error,
            'sent_at' => $status === NotificationDispatch::STATUS_SENT ? now() : null,
        ]);

        return $row;
    }
}
