<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.1 — Billing module: catalogue of subscription plans.
 *
 * Multi-tenant by `project_id` (nullable → platform-wide plan offered to all
 * tenants). Pricing is stored in cents in a fixed currency. Provider IDs
 * (stripe_*) are nullable so we can author plans before syncing them with
 * Stripe at deploy time (Sprint 16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time'])->default('monthly');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);

            // Provider integration (nullable until Sprint 16 sync)
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['is_active', 'billing_cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
