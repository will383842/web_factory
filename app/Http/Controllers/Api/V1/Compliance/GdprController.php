<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Compliance;

use App\Http\Controllers\Controller;
use App\Models\AutomationRequest;
use App\Models\BillingCustomer;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 24 — GDPR / Article 15 (export) + Article 17 (right to erasure).
 *
 * Both endpoints require Sanctum authentication and operate on the caller's
 * own user only. Admins can use the Filament resources to act on others.
 */
final class GdprController extends Controller
{
    /**
     * Article 15 — data export. Returns every row tied to the user as JSON.
     */
    public function export(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'email_verified_at']),
            'sso_identities' => $user->ssoIdentities()->get(['provider', 'provider_user_id', 'email', 'created_at']),
            'team_memberships' => TeamMember::query()
                ->where('user_id', $user->getKey())
                ->with('team:id,slug,name')
                ->get(['team_id', 'role', 'joined_at']),
            'team_invitations_received' => TeamInvitation::query()->where('email', $user->email)->get(),
            'billing_customers' => BillingCustomer::query()->where('user_id', $user->getKey())->get(),
            'notification_dispatches' => NotificationDispatch::query()->where('user_id', $user->getKey())->limit(500)->get(),
            'notification_preferences' => NotificationPreference::query()->where('user_id', $user->getKey())->get(),
            'automation_requests' => AutomationRequest::query()->where('email', $user->email)->get(),
            'exported_at' => now()->toAtomString(),
        ])->header('Content-Disposition', 'attachment; filename="user-'.$user->getKey().'-gdpr-export.json"');
    }

    /**
     * Article 17 — right to erasure. Cascades via FK constraints; transactional log
     * is anonymized rather than deleted (legal/financial retention).
     */
    public function delete(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            // Anonymize transactional rows we must keep for legal reasons.
            BillingCustomer::query()->where('user_id', $user->getKey())->update([
                'email' => 'anonymized+'.$user->getKey().'@example.invalid',
                'name' => null,
            ]);
            NotificationDispatch::query()->where('user_id', $user->getKey())->update([
                'recipient' => 'anonymized',
                'payload' => null,
            ]);

            // Cascade-delete everything else via FK constraints
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->noContent();
    }
}
