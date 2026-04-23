<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_profiles', function (Blueprint $table) {
            $table->id();

            // Strict 1:1 with users. Created when user enables marketplace.
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('headline', 200);        // e.g. "Full-Stack Laravel Developer"
            $table->text('bio')->nullable();         // Markdown supported

            $table->enum('profession_category', [
                'software_dev',
                'mobile_dev',
                'design',
                'marketing',
                'video_production',
                'writing',
                'consulting',
                'engineering',
                'education',
                'data_science',
                'devops',
                'other',
            ])->default('other');

            $table->json('skills')->nullable();             // ["Laravel","Vue","MySQL","Docker"]
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('currency', 3)->default('USD'); // ISO 4217
            $table->tinyInteger('years_experience')->unsigned()->nullable();
            $table->string('location', 150)->nullable();   // e.g. "Yaoundé, Cameroon"
            $table->json('languages')->nullable();          // ["French","English"]

            $table->enum('availability_status', [
                'open_to_work',
                'busy',
                'not_available',
            ])->default('open_to_work');

            // Avg response time in hours
            $table->tinyInteger('response_time_hours')->unsigned()->nullable();

            // Denormalized: recalculated by Eloquent observer on every review INSERT/UPDATE
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->smallInteger('total_reviews')->unsigned()->default(0);

            // Set by platform admin after identity verification
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['profession_category', 'availability_status']);
            $table->index(['avg_rating', 'total_reviews']);
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_profiles');
    }
};
