<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            // The professional who offers this service
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->enum('category', [
                'web_dev',
                'mobile_dev',
                'design',
                'data',
                'devops',
                'marketing',
                'video',
                'writing',
                'consulting',
                'engineering',
                'education',
                'software_dev',
                'other',
            ])->default('other');

            $table->enum('delivery_type', [
                'fixed_price',
                'hourly',
                'per_project',
            ])->default('fixed_price');

            $table->decimal('price_from', 10, 2);           // Starting / minimum price
            $table->decimal('price_to', 10, 2)->nullable(); // NULL if single fixed price
            $table->string('currency', 3)->default('USD');  // ISO 4217

            // Estimated delivery / turnaround in days
            $table->smallInteger('delivery_days')->unsigned()->nullable();

            // false = hidden from marketplace browse
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index(['delivery_type', 'price_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
