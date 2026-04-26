<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 14 — Automation requests (B2B lead capture from the public CTA modal).
 *
 * The "Demande d'automatisation" modal posts here. The Sprint 13.4 dispatcher
 * fans the row out to email + Telegram on creation. `category` ties the lead
 * to a Marketing taxonomy entry (Sprint 11). Status follows the lifecycle
 * new → contacted → qualified → won / lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_country_code', 8);
            $table->string('phone_number', 32);
            $table->string('company')->nullable();
            $table->string('category');
            $table->text('message');
            $table->boolean('rgpd_accepted')->default(false);
            $table->enum('status', ['new', 'contacted', 'qualified', 'won', 'lost'])->default('new');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('source')->nullable();
            $table->json('utm')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_requests');
    }
};
