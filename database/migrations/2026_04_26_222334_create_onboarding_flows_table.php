<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.3 — Onboarding flows.
 *
 * One flow per persona (admin / user / team-owner / ...). The `steps` JSON
 * stores an ordered list of {key, title, icon, cta_url, weight} objects.
 * `audience` is a simple match-by-role string (see ActivationScoreCalculator).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('audience')->default('user');
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['audience', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_flows');
    }
};
