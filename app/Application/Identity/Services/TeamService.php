<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sprint 13.2 — orchestrates team lifecycle: creation, invitations, joins,
 * removal, ownership transfer. Pure application service: no Filament, no
 * HTTP — just domain logic + persistence via Eloquent.
 *
 * Returns plain Eloquent models (acceptable per ArchTest §3 — Application
 * MAY reference App\Models since they're our DDD entities under the
 * implicit contract that Eloquent models live in the App\Models namespace).
 */
final class TeamService
{
    /**
     * @param array<string, mixed> $settings
     */
    public function createTeam(User $owner, string $name, ?string $slug = null, array $settings = []): Team
    {
        return DB::transaction(function () use ($owner, $name, $slug, $settings): Team {
            $team = Team::query()->create([
                'owner_id' => $owner->getKey(),
                'slug' => $slug ?? Str::slug($name).'-'.Str::lower(Str::random(6)),
                'name' => $name,
                'settings' => $settings,
            ]);

            TeamMember::query()->create([
                'team_id' => $team->getKey(),
                'user_id' => $owner->getKey(),
                'role' => Team::ROLE_OWNER,
                'joined_at' => now(),
            ]);

            return $team;
        });
    }

    /**
     * Issue an invitation. Returns the raw token — caller is responsible for
     * sending it to the invitee (the hash is what we persist).
     *
     * @return array{invitation: TeamInvitation, raw_token: string}
     */
    public function inviteMember(Team $team, User $inviter, string $email, string $role = Team::ROLE_MEMBER): array
    {
        $rawToken = Str::random(48);

        $invitation = TeamInvitation::query()->create([
            'team_id' => $team->getKey(),
            'invited_by' => $inviter->getKey(),
            'email' => $email,
            'role' => $role,
            'token_hash' => TeamInvitation::hashToken($rawToken),
            'status' => TeamInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        return ['invitation' => $invitation, 'raw_token' => $rawToken];
    }

    public function acceptInvitation(string $rawToken, User $acceptingUser): TeamMember
    {
        return DB::transaction(function () use ($rawToken, $acceptingUser): TeamMember {
            $invitation = TeamInvitation::query()
                ->where('token_hash', TeamInvitation::hashToken($rawToken))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isPending()) {
                throw new DomainException('Invitation is no longer pending or has expired');
            }

            $member = TeamMember::query()->create([
                'team_id' => $invitation->team_id,
                'user_id' => $acceptingUser->getKey(),
                'role' => $invitation->role,
                'joined_at' => now(),
            ]);

            $invitation->forceFill([
                'status' => TeamInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_by' => $acceptingUser->getKey(),
            ])->save();

            return $member;
        });
    }

    public function removeMember(Team $team, User $user): void
    {
        TeamMember::query()
            ->where('team_id', $team->getKey())
            ->where('user_id', $user->getKey())
            ->where('role', '!=', Team::ROLE_OWNER)
            ->delete();
    }

    public function transferOwnership(Team $team, User $newOwner): Team
    {
        return DB::transaction(function () use ($team, $newOwner): Team {
            // Demote previous owner to admin
            TeamMember::query()
                ->where('team_id', $team->getKey())
                ->where('user_id', $team->owner_id)
                ->update(['role' => Team::ROLE_ADMIN]);

            // Promote new owner (upsert their membership)
            TeamMember::query()->updateOrCreate(
                ['team_id' => $team->getKey(), 'user_id' => $newOwner->getKey()],
                ['role' => Team::ROLE_OWNER, 'joined_at' => now()],
            );

            $team->forceFill(['owner_id' => $newOwner->getKey()])->save();

            return $team->fresh() ?? $team;
        });
    }
}
