<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Who wrote the review
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();

            // Who is being rated (the professional)
            $table->foreignId('reviewee_id')->constrained('users')->restrictOnDelete();

            // Optional context: what real transaction backs this review?
            // At least one of these should be set for is_verified = true.
            // project_id and booking_id FKs added after those tables are created.
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();

            // Org collaboration review
            $table->foreignId('org_id')
                  ->nullable()
                  ->constrained('organizations')
                  ->nullOnDelete();

            $table->tinyInteger('rating')->unsigned(); // 1–5 stars

            $table->string('title', 200)->nullable();
            $table->text('body')->nullable();

            // TRUE when project_id OR booking_id is populated.
            // Backed by a real transaction — cannot be purchased or faked.
            $table->boolean('is_verified')->default(false);

            $table->timestamp('created_at')->useCurrent();

            // One review per reviewer per project and per booking
            $table->unique(['reviewer_id', 'reviewee_id', 'project_id'], 'unique_review_project');
            $table->unique(['reviewer_id', 'reviewee_id', 'booking_id'], 'unique_review_booking');

            $table->index('reviewee_id');
            $table->index(['reviewee_id', 'is_verified']);
        });

        // Rating constraint: must be between 1 and 5
        // Added as a raw statement since Blueprint doesn't support CHECK natively in all drivers
        // DB::statement('ALTER TABLE reviews ADD CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
