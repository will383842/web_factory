<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.1 — Billing module: subscriptions (Customer × Plan).
 *
 * Status follows Stripe-ish lifecycle (trialing/active/past_due/canceled/...).
 * `current_period_*` snapshot the live billing window so dashboards can show
 * "renews on X" without an extra API hop. Trial/cancel timestamps are kept
 * separate so we can compute MRR/ARR/churn without re-deriving from logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('billing_customers')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('billing_plans')->restrictOnDelete();

            $table->enum('status', [
                'trialing', 'active', 'past_due', 'canceled', 'unpaid', 'incomplete', 'incomplete_expired',
            ])->default('incomplete');

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            $table->string('stripe_subscription_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('stripe_subscription_id');
            $table->index('current_period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_subscriptions');
    }
};
