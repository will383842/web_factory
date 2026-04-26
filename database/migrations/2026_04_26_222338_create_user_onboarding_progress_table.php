<?php

declare(strict_types=1);

use App\Application\Marketing\Services\ActivationScoreCalculator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.3 — Per-user progress through an onboarding flow.
 *
 * `completed_steps` is the JSON array of step keys the user has completed.
 * `score` is the cached output of {@see ActivationScoreCalculator}
 * — a 0-100 weighted percentage of completed step weights.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained('onboarding_flows')->cascadeOnDelete();
            $table->json('completed_steps');
            $table->unsignedTinyInteger('score')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'flow_id']);
            $table->index('score');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
};
