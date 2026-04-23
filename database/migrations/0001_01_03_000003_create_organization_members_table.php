<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', [
                'owner',
                'admin',
                'project_manager',
                'member',
                'viewer',
            ])->default('member');
            $table->timestamp('joined_at')->useCurrent();

            // One membership per user per org
            $table->unique(['org_id', 'user_id']);
            $table->index(['org_id', 'role']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};
