<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.4 — Notification templates (per event_type, per channel, per locale).
 *
 * `subject` is for email/SMS. `body` is the template body (Blade-like
 * placeholders {{ var }}). `payload_schema` is the JSON shape the dispatcher
 * should pass at runtime — informational only, not enforced (Sprint 16 may
 * add JSON-schema validation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('channel');
            $table->string('locale', 12)->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('payload_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'event_type', 'channel', 'locale']);
            $table->index(['event_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
