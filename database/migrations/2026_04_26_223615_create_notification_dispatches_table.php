<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.4 — Notification dispatch log (one row per attempted send).
 *
 * `status` covers the lifecycle queued → sent → delivered / failed / bounced.
 * `external_id` stores the provider message id (Postmark MessageID, Twilio
 * SID, OneSignal id) so we can correlate inbound webhooks with our row and
 * track open/click/bounce events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('channel');
            $table->string('event_type');
            $table->string('recipient');
            $table->json('payload')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'bounced', 'skipped'])->default('queued');
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['user_id', 'event_type']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');
    }
};
