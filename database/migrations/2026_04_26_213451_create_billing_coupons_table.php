<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.1 — Billing module: discount coupons.
 *
 * Either `percent_off` (1-100) OR (`amount_off` + `currency`). `redeemed_count`
 * is denormalized so admin tables can sort/filter without a JOIN. Stripe IDs
 * are filled lazily on first sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('code');
            $table->string('name')->nullable();

            $table->unsignedTinyInteger('percent_off')->nullable();
            $table->unsignedBigInteger('amount_off')->nullable();
            $table->string('currency', 3)->nullable();

            $table->enum('duration', ['once', 'repeating', 'forever'])->default('once');
            $table->unsignedSmallInteger('duration_in_months')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);

            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('stripe_coupon_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'code']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_coupons');
    }
};
