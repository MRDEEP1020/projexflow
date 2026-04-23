<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                  ->constrained('contracts')
                  ->restrictOnDelete();

            // Who raised the dispute
            $table->foreignId('raised_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // The other party
            $table->foreignId('against')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->enum('reason', [
                'work_not_delivered',
                'quality_issues',
                'scope_disagreement',
                'payment_issue',
                'other',
            ]);

            $table->text('description');            // Full description of the issue

            // Array of screenshot URLs, document links, or other evidence
            $table->json('evidence_urls')->nullable();

            // Meeting recording and transcript used as legal proof
            $table->foreignId('meeting_room_id')
                  ->nullable()
                  ->constrained('meeting_rooms')
                  ->nullOnDelete();

            $table->enum('status', [
                'open',                     // Just raised, no admin action yet
                'under_review',             // Admin is reviewing evidence
                'resolved_for_client',      // Admin ruled: refund to client
                'resolved_for_freelancer',  // Admin ruled: release to freelancer
                'closed',                   // Resolved or dismissed
            ])->default('open');

            $table->text('admin_notes')->nullable(); // Internal admin decision notes
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index(['raised_by', 'status']);
            $table->index('status'); // For admin dispute queue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
