<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.1 — Billing module: tenant-scoped customers (a User × a Project).
 *
 * `stripe_customer_id` is nullable (filled at first checkout). One row per
 * (project_id, user_id) pair so the same user can have separate billing
 * profiles across the tenants they belong to (Sprint 13.2 Teams introduces
 * a Team variant — same shape, just owner=team_id instead of user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('default_payment_method')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_customers');
    }
};
