<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('locale', 15)->default('fr');
            $table->string('primary_domain', 191)->nullable();
            $table->unsignedSmallInteger('virality_score')->default(0);
            $table->unsignedSmallInteger('value_score')->default(0);
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'owner_id']);
            $table->index('virality_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
