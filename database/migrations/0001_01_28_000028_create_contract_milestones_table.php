<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();

            $table->string('title', 200);           // e.g. "Phase 1 — Design"
            $table->text('description')->nullable(); // What deliverables trigger release

            // Portion of the total contract amount for this phase
            $table->decimal('amount', 12, 2);

            $table->date('due_date')->nullable();

            $table->enum('status', [
                'pending',      // Not yet funded by client
                'funded',       // Client has pre-funded this milestone
                'submitted',    // Freelancer submitted work for this milestone
                'released',     // Payment released to freelancer
                'disputed',     // Dispute raised specifically on this milestone
            ])->default('pending');

            // When funds were released to the freelancer's wallet
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};
