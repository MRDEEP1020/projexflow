<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Must be <= wallets.available_balance at time of request
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            $table->enum('method', [
                'bank_transfer',    // International wire
                'mobile_money',     // MTN, Orange (via CinetPay / Flutterwave)
                'stripe',           // Stripe Express payout
                'paypal',           // PayPal transfer
                'flutterwave',      // Flutterwave direct payout
                'cinetpay',         // CinetPay (Cameroon / West Africa)
            ]);

            // ENCRYPTED at rest — contains sensitive payout details:
            // bank: { account_number, bank_name, swift, account_name }
            // mobile_money: { phone_number, operator, country }
            // stripe: { stripe_account_id }
            // paypal: { email }
            $table->json('account_details');

            $table->enum('status', [
                'pending',      // Submitted, awaiting processing
                'processing',   // Payout initiated with provider
                'completed',    // Funds sent successfully
                'failed',       // Provider rejected — funds returned to wallet
            ])->default('pending');

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']); // For admin payout queue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
