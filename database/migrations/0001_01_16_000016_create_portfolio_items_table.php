<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();

            // The developer who owns this portfolio entry
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Optional: link to an archived platform project.
            // NULL = external project added manually (not on this platform)
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();    // What they specifically did on this project
            $table->string('cover_image', 500)->nullable(); // S3 path — shown on portfolio card
            $table->json('tech_stack')->nullable();     // ["Laravel", "Vue", "MySQL"]
            $table->string('project_url', 500)->nullable();  // Live URL
            $table->string('github_url', 500)->nullable();   // Public repo

            $table->boolean('is_featured')->default(false);  // Shown first on profile
            $table->boolean('is_public')->default(true);     // Visible on marketplace profile
            $table->smallInteger('sort_order')->unsigned()->default(0); // Drag-to-reorder
            $table->timestamps();

            $table->index(['user_id', 'is_public']);
            $table->index(['user_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
