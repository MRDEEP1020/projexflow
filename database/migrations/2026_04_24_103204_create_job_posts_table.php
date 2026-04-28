<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // job_posts table
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hired_freelancer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->enum('type', ['fixed', 'hourly'])->default('fixed');
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'expert'])->default('mid');
            $table->json('skills_required')->nullable();
            $table->string('duration')->nullable();
            $table->date('deadline')->nullable();
            $table->enum('visibility', ['public', 'invite_only'])->default('public');
            $table->unsignedInteger('max_applicants')->default(20);
            $table->enum('status', ['draft', 'open', 'filled', 'closed','removed'])->default('draft');
            $table->timestamps();

            $table->index(['status', 'visibility', 'category']);
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
