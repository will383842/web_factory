<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.4 — Per-user opt-in/out matrix (channel × event_type).
 *
 * One row per (user_id, channel, event_type). `enabled=false` is an explicit
 * opt-out (RGPD-compliant). The dispatcher MUST consult this table before
 * sending any non-transactional message. Transactional messages (security
 * alerts, password resets) bypass preferences — that policy lives in the
 * dispatcher, not in the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel');
            $table->string('event_type');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
