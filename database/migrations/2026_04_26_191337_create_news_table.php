<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('slug', 191);
            $table->string('locale', 15);
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('source_url', 500)->nullable();
            $table->string('category', 64)->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'slug', 'locale']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
