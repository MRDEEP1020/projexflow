<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Optional: group under a milestone
            $table->foreignId('milestone_id')
                  ->nullable()
                  ->constrained('milestones')
                  ->nullOnDelete();

            // Self-referencing: subtasks point to their parent
            $table->foreignId('parent_task_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->cascadeOnDelete();

            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Core fields
            $table->string('title', 300);
            $table->text('description')->nullable();    // Markdown

            $table->enum('status', [
                'todo',
                'in_progress',
                'in_review',
                'done',
                'blocked',
            ])->default('todo');

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'critical',
            ])->default('medium');

            // Time scope for calendar grouping
            $table->enum('cadence', [
                'daily',
                'weekly',
                'monthly',
                'yearly',
                'milestone',
            ])->default('weekly');

            $table->date('due_date')->nullable();       // Feeds personal calendar view
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // GitHub integration (populated by webhook job)
            $table->string('pr_url', 500)->nullable();
            $table->unsignedInteger('pr_number')->nullable();
            $table->string('commit_sha', 40)->nullable();
            $table->string('github_branch', 100)->nullable();

            // Deliverable evidence
            $table->enum('deliverable_type', [
                'none',
                'github_pr',
                'url',
                'file_upload',
                'figma',
                'screenshot',
                'google_doc',
                'video_url',
                'pdf',
                'other',
            ])->default('none');
            $table->string('deliverable_url', 500)->nullable();
            $table->text('deliverable_note')->nullable();

            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            // Query-critical indexes
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'milestone_id']);
            $table->index(['assigned_to', 'status']);
            $table->index(['assigned_to', 'due_date']);
            $table->index('due_date');
            $table->index('parent_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
