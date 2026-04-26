<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('slug', 191);
            $table->string('locale', 15);
            $table->string('title', 255);
            $table->string('type', 32)->default('static'); // home, static, pricing, form, legal, index
            $table->string('status', 16)->default('draft'); // draft, scheduled, published, archived
            $table->timestamp('published_at')->nullable();
            $table->json('content_blocks')->default('[]');
            $table->json('meta_tags')->default('{}');
            $table->timestamps();

            $table->unique(['project_id', 'slug', 'locale']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
