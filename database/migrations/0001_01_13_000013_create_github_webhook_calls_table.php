<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Managed by spatie/laravel-github-webhooks package.
        // Stores raw GitHub payloads before processing.
        // Enables debugging and job retry without contacting GitHub again.
        Schema::create('github_webhook_calls', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250);        // Event type: push, pull_request, etc.
            $table->text('url');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable(); // Full GitHub payload
            $table->text('exception')->nullable(); // Error if processing failed

            // NULL = not yet processed
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['name', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_webhook_calls');
    }
};
