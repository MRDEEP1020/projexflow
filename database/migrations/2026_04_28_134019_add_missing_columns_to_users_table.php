<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add slug column if missing
            if (!Schema::hasColumn('users', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('avatar_url');
            }
            
            // Add stripe_connect_id if missing
            if (!Schema::hasColumn('users', 'stripe_connect_id')) {
                $table->string('stripe_connect_id')->nullable()->after('slug');
            }
            
            // Add any other columns your UserFactory is using
            if (!Schema::hasColumn('users', 'is_marketplace_enabled')) {
                $table->boolean('is_marketplace_enabled')->default(false)->after('active_mode');
            }
            
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['slug', 'stripe_connect_id', 'is_marketplace_enabled', 'suspended_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};