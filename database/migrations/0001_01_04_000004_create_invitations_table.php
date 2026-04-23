<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('email', 191);
            $table->enum('role', [
                'admin',
                'project_manager',
                'member',
                'viewer',
            ])->default('member');
            $table->string('token', 64)->unique(); // 64-char secure random
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();  // NULL = still pending
            $table->timestamp('expires_at')->nullable();               // created_at + 7 days
            $table->timestamp('created_at')->useCurrent();

            $table->index(['org_id', 'email']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
