<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Event type — drives icon and color in UI
            $table->string('type', 100);            // e.g. task_assigned, pr_merged, booking_confirmed

            $table->string('title', 255);
            $table->string('body', 500)->nullable();
            $table->string('url', 500)->nullable(); // Navigate-to on click

            // NULL = unread
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Bell icon query: WHERE user_id = ? AND read_at IS NULL
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
