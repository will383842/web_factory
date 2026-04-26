<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 13.2 — SSO identity links (Socialite providers).
 *
 * One row per (user_id, provider) pair. `provider_user_id` is the upstream
 * stable identifier (Google sub, Microsoft oid, Apple sub, Okta sub). Tokens
 * are stored encrypted via Laravel Crypt cast at the model level; the raw
 * column is text because encrypted blobs can be quite long.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('email')->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_identities');
    }
};
