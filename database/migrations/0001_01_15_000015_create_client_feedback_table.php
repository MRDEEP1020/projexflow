<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Feedback targets either a milestone OR a task (not both required)
            $table->foreignId('milestone_id')
                  ->nullable()
                  ->constrained('milestones')
                  ->nullOnDelete();

            $table->foreignId('task_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->nullOnDelete();

            // Client may or may not have an account
            $table->string('client_name', 150)->nullable();
            $table->string('client_email', 191)->nullable();

            $table->text('body');
            $table->enum('type', [
                'comment',
                'approval',
                'revision_request',
            ])->default('comment');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_feedback');
    }
};
