<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            // The two parties
            $table->foreignId('client_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->foreignId('freelancer_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Optional context links
            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained('services')
                  ->nullOnDelete();

            $table->foreignId('booking_id')
                  ->nullable()
                  ->constrained('bookings')
                  ->nullOnDelete();

            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable(); // Full scope agreed by both parties

            // Financial terms
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            // Deposit as a percentage of total (e.g. 30 means 30%)
            $table->tinyInteger('deposit_percentage')->unsigned()->default(30);

            // Calculated: total_amount * deposit_percentage / 100
            $table->decimal('deposit_amount', 12, 2);

            // ProjexFlow's commission (e.g. 10.00 = 10%)
            $table->decimal('platform_fee_percentage', 4, 2)->default(10.00);

            $table->enum('status', [
                'draft',        // Created, awaiting client deposit
                'active',       // Deposit paid, work in progress
                'submitted',    // Freelancer submitted work, awaiting client review
                'completed',    // Client confirmed OR auto-released after 7 days
                'disputed',     // Dispute raised — funds frozen
                'cancelled',    // Cancelled before deposit was paid
                'refunded',     // Refunded to client after dispute resolution
            ])->default('draft');

            // Set when freelancer clicks "Submit Work"
            $table->timestamp('work_submitted_at')->nullable();

            // Auto-release trigger: work_submitted_at + 7 days.
            // A cron job checks daily: WHERE auto_release_at <= NOW() AND status = 'submitted'
            $table->timestamp('auto_release_at')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['freelancer_id', 'status']);
            $table->index(['status', 'auto_release_at']); // For the cron job query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
