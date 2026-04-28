<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // job_applications table
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->decimal('proposed_rate', 10, 2)->nullable();
            $table->string('availability')->nullable();
            $table->enum('status', ['pending', 'shortlisted', 'hired', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['job_post_id', 'freelancer_id']); // one app per job per freelancer
            $table->index(['freelancer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
