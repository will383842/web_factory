<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.2 — Team membership pivot.
 *
 * `role` is the team-level role (owner / admin / member), distinct from the
 * platform-level role on `users` (admin / editor / user via Spatie). One
 * (team_id, user_id) is unique so a user can only join a team once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
