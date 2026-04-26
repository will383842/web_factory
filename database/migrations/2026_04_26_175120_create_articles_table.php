<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('slug', 191);
            $table->string('locale', 15);
            $table->string('title', 255);
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('featured_image_url', 500)->nullable();
            $table->json('seo_keywords')->default('[]');
            $table->boolean('is_pillar')->default(false);
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedSmallInteger('reading_time_minutes')->default(0);
            $table->unsignedSmallInteger('quality_score')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug', 'locale']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
