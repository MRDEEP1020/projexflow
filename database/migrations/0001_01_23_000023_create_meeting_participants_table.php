<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('meeting_rooms')->cascadeOnDelete();

            // NULL when participant is a guest (not a registered user)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Guest fields (used when user_id is NULL)
            $table->string('guest_name', 150)->nullable();
            $table->string('guest_email', 191)->nullable();

            $table->enum('role', ['host', 'participant', 'observer'])->default('participant');

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();   // NULL = still in the call

            $table->index(['room_id', 'user_id']);
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
