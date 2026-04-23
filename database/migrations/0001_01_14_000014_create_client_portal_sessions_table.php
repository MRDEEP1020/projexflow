<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('client_email', 191)->nullable();
            $table->string('ip_address', 45)->nullable();   // IPv4 or IPv6
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_sessions');
    }
};
