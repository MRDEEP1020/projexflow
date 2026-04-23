<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('filename', 255);       // Original filename shown to user
            $table->string('disk_path', 500);      // S3 / local path
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_files');
    }
};
