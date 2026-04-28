<?php

// ══════════════════════════════════════════════════════════════════
// database/migrations/xxxx_add_active_mode_to_users_table.php
// ══════════════════════════════════════════════════════════════════
// php artisan make:migration add_active_mode_to_users_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // active_mode: which interface the user is currently in
            $table->enum('active_mode', ['client', 'freelancer'])
                ->default('client')
                ->after('remember_token');

            // role: platform-level role for admin access
            $table->enum('role', ['user', 'moderator', 'admin'])
                ->default('user')
                ->after('active_mode');

            // suspended_at: soft suspension without deleting account
            $table->timestamp('suspended_at')
                ->nullable()
                ->after('role');

            $table->index('role');
            $table->index('active_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['active_mode', 'role', 'suspended_at']);
        });
    }
};
