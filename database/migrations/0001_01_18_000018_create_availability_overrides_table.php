<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');                       // The specific date being overridden
            $table->boolean('is_available');            // false = blocked, true = open (even if weekend)
            $table->string('reason', 200)->nullable();  // e.g. "Holiday", "Conference", "Sick day"
            $table->timestamp('created_at')->useCurrent();

            // One override per user per date
            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_overrides');
    }
};
