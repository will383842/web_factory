<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Activate the pgvector extension (idempotent).
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('knowledge_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('source_type', 32);  // page, article, faq, manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('topic', 191)->nullable();
            $table->string('locale', 15);
            $table->longText('content');
            $table->unsignedInteger('content_tokens')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'source_type']);
            $table->index(['project_id', 'locale']);
        });

        // 384-dim is the size produced by our deterministic Sprint-7
        // embedding service. Sprint 19 will bump this to 1536 (OpenAI
        // text-embedding-3-small) when the real adapter lands.
        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(384)');

        // HNSW index for cosine-distance lookups.
        DB::statement('CREATE INDEX knowledge_chunks_embedding_hnsw ON knowledge_chunks USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
