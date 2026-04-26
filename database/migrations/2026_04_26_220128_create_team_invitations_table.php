<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.2 — Team invitations (email + signed link, 7-day default).
 *
 * `token` is sha256-hashed at write time; the raw token only lives in the
 * acceptance URL sent to the invitee. Status follows pending → accepted /
 * revoked / expired. (team_id, email) is unique while pending — re-inviting a
 * canceled person creates a new row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->string('token_hash', 64);
            $table->enum('status', ['pending', 'accepted', 'revoked', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('token_hash');
            $table->index(['team_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
