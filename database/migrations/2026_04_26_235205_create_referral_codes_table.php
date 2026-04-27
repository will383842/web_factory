<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 22 — Virality / Referral codes.
 *
 * Each user can have one personal sharing code. `redeemed_count` is
 * denormalized for leaderboard queries. `bonus_credits_cents` is the per-
 * referral payout that the Sprint 13.1 billing layer credits on signup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->unsignedInteger('bonus_credits_cents')->default(0);
            $table->timestamps();

            $table->index(['owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};
