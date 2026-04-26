<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.1 — Billing module: per-period invoices.
 *
 * `subscription_id` is nullable because one-shot invoices are valid (e.g. an
 * add-on charge that does not belong to a recurring plan). Status is the
 * superset of the Stripe and Paddle invoice lifecycles. PDF URL is provider-
 * managed and stored verbatim so admins can download without proxying.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('billing_customers')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('billing_subscriptions')->nullOnDelete();

            $table->string('number')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->string('currency', 3)->default('EUR');

            $table->enum('status', ['draft', 'open', 'paid', 'uncollectible', 'void'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_at')->nullable();

            $table->string('stripe_invoice_id')->nullable();
            $table->string('pdf_url')->nullable();
            $table->json('line_items')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
    }
};
