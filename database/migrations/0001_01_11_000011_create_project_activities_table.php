<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Optional: which task triggered this event
            $table->foreignId('task_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->nullOnDelete();

            // NULL when triggered by GitHub webhook (no user)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('type', [
                'task_created',
                'task_updated',
                'task_completed',
                'task_assigned',
                'comment_added',
                'file_uploaded',
                'pr_opened',
                'pr_merged',
                'commit_pushed',
                'milestone_completed',
                'member_joined',
                'project_created',
                'project_archived',
            ]);

            $table->string('description', 500);    // Human-readable message
            $table->string('github_url', 500)->nullable();  // Link to PR / commit
            $table->string('actor', 100)->nullable();       // GitHub username (from webhook)
            $table->json('metadata')->nullable();           // Extra context if needed

            // Append-only: never update, only insert
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at']);
            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
    }
};
