<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: booking_id FK is added in migration 0025 (create_bookings)
        // to resolve the circular dependency: bookings needs meeting_rooms,
        // and meeting_rooms needs bookings.
        // This migration creates the table WITHOUT the booking_id FK.
        // Migration 0025 adds the FK via an ALTER after both tables exist.
        Schema::create('meeting_rooms', function (Blueprint $table) {
            $table->id();

            // booking_id is defined as plain unsignedBigInteger here.
            // The FK constraint is added in migration 0025_add_booking_fk_to_meeting_rooms.
            $table->unsignedBigInteger('booking_id')->nullable();

            // Can also be a standalone project team meeting
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Org-level meeting (not tied to a specific project)
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->string('title', 200);

            // LiveKit room name — globally unique identifier used to join the room
            $table->string('room_token', 100)->unique();

            $table->enum('status', [
                'scheduled',
                'live',
                'ended',
            ])->default('scheduled');

            $table->timestamp('started_at')->nullable();    // Set when first participant joins
            $table->timestamp('ended_at')->nullable();      // Set when room closes
            $table->unsignedInteger('duration_seconds')->nullable(); // Calculated on end
            $table->timestamps();

            $table->index(['booking_id']);
            $table->index(['project_id', 'status']);
            $table->index('room_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_rooms');
    }
};
