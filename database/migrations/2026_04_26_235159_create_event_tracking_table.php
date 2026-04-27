<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 20 — Growth / CRO event tracking.
 *
 * Generic JSONB-friendly event log: `name` is the funnel step (page_view,
 * cta_click, signup, purchase), `properties` is free-form. `session_id`
 * groups events for funnel analysis without requiring a logged-in user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->string('name');
            $table->json('properties')->nullable();
            $table->string('source', 80)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['project_id', 'name', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tracking');
    }
};
