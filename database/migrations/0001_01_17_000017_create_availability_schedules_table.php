<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            $table->tinyInteger('day_of_week')->unsigned();

            $table->time('start_time');     // e.g. 09:00:00
            $table->time('end_time');       // e.g. 18:00:00

            // false = blocked this day (e.g. weekends off)
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            // One schedule row per day per user
            $table->unique(['user_id', 'day_of_week']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_schedules');
    }
};
