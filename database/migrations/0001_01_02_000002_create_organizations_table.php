<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique(); // URL-safe, globally unique
            $table->string('logo', 500)->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['company', 'personal'])->default('company');
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->timestamps();

            $table->index('slug');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
