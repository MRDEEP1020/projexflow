<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            // Strict 1:1 with users. Created on first earnings event.
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Funds fully released and ready to withdraw
            $table->decimal('available_balance', 12, 2)->default(0.00);

            // Funds held in escrow — not yet released to freelancer
            $table->decimal('held_balance', 12, 2)->default(0.00);

            // Lifetime earnings counter (never decremented on withdrawal)
            $table->decimal('total_earned', 12, 2)->default(0.00);

            $table->string('currency', 3)->default('USD'); // ISO 4217

            // updated_at tracks last balance change — useful for audit
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Balance update rules (enforced at application layer):
            // On deposit:    held_balance    += deposit_amount
            // On release:    held_balance    -= amount
            //                available_balance += (amount - platform_fee)
            // On withdrawal: available_balance -= withdrawal_amount
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
