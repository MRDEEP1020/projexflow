<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // NULL = personal workspace project (not tied to any org)
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->cascadeOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();

            $table->enum('status', [
                'planning',
                'active',
                'on_hold',
                'completed',
                'cancelled',
            ])->default('planning');

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'critical',
            ])->default('medium');

            // 0–100. Auto-calculated when tasks complete.
            $table->tinyInteger('progress_percentage')->unsigned()->default(0);

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Archive: NULL = active. Set = archived.
            // No extra table needed — just filter by this column.
            $table->timestamp('archived_at')->nullable();

            // GitHub integration (optional)
            $table->string('github_repo', 200)->nullable();   // format: owner/repo
            $table->string('github_branch', 100)->nullable()->default('main');

            // Client portal (token-based, no account required)
            $table->string('client_token', 64)->unique();     // 64-char cryptographic random
            $table->boolean('client_portal_enabled')->default(false);
            $table->string('client_name', 150)->nullable();
            $table->string('client_email', 191)->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Core query indexes
            $table->index(['org_id', 'status']);
            $table->index(['org_id', 'archived_at']);
            $table->index('client_token');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
