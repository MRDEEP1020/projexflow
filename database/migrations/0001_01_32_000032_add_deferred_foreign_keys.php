<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // reviews.project_id was created as a plain unsignedBigInteger in migration 0021
        // because projects existed but we deferred FKs for reviews to keep ordering clean.
        // Adding the FK constraint here now that all referenced tables exist.
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
};
