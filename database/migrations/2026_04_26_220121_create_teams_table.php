<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.2 — Teams (B2B workspaces).
 *
 * A Team is the new owner of a Subscription (Spec 29 §2.B2B). The owner User
 * is the payer; other members consume the seats granted by the plan. Slug is
 * unique platform-wide so it can be used in URLs (`/teams/{slug}`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
