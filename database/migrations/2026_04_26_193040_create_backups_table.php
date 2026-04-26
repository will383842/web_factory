<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            // Optional project scope — null means a platform-wide backup.
            $table->foreignId('project_id')->nullable()
                ->constrained('projects')->nullOnDelete();
            $table->string('kind', 16);            // full, incremental, snapshot
            $table->string('target', 32);          // local, r2, b2, gdrive, borg
            $table->string('status', 16)->default('running'); // running, succeeded, failed
            $table->string('archive_path', 500)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->json('manifest')->default('{}');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'kind']);
            $table->index(['target', 'status']);
            $table->index('finished_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
