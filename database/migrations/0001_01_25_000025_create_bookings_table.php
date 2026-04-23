<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── STEP 1: Create bookings ──────────────────────────────────────
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // The professional who is being booked
            $table->foreignId('provider_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // The client who made the booking.
            // NULL when the client has no platform account (guest booking).
            $table->foreignId('client_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Guest fields (used when client_id is NULL)
            $table->string('client_email', 191)->nullable();
            $table->string('client_name', 150)->nullable();

            // Which service listing was booked (optional — can be a free-form booking)
            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained('services')
                  ->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable(); // Client's brief for the session

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('timezone', 50);         // Stored at booking time (IANA)

            $table->enum('status', [
                'pending',      // Awaiting provider confirmation
                'confirmed',    // Provider confirmed — meeting room created
                'cancelled',    // Declined or cancelled by either party
                'completed',    // Session happened successfully
                'no_show',      // Client or provider didn't appear
            ])->default('pending');

            // Set when provider confirms — links to the LiveKit room
            $table->foreignId('meeting_room_id')
                  ->nullable()
                  ->constrained('meeting_rooms')
                  ->nullOnDelete();

            $table->text('provider_notes')->nullable(); // Internal notes after session

            $table->timestamps();

            $table->index(['provider_id', 'start_at']);
            $table->index(['provider_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('start_at');
        });

        // ── STEP 2: Resolve circular dependency ─────────────────────────
        // meeting_rooms.booking_id was created without a FK in migration 0022
        // because bookings didn't exist yet.
        // Now that bookings exists, we add the FK constraint here.
        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->foreign('booking_id')
                  ->references('id')
                  ->on('bookings')
                  ->nullOnDelete();
        });

        // ── STEP 3: Add FKs to reviews that depend on bookings ──────────
        // reviews.booking_id was created as plain unsignedBigInteger in migration 0021.
        // Add the FK constraint now that bookings exists.
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('booking_id')
                  ->references('id')
                  ->on('bookings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Drop FKs before dropping tables
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });

        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });

        Schema::dropIfExists('bookings');
    }
};
