<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            // ── CRITICAL: This table is APPEND-ONLY. ──────────────────────
            // Never UPDATE or DELETE a row. Every money event creates a new row.
            // Wallet balances are always reconcilable by summing transactions.
            // ────────────────────────────────────────────────────────────────

            $table->foreignId('contract_id')
                  ->nullable()
                  ->constrained('contracts')
                  ->nullOnDelete();

            $table->foreignId('milestone_id')
                  ->nullable()
                  ->constrained('contract_milestones')
                  ->nullOnDelete();

            // NULL for system-triggered events (auto-release, cron)
            $table->foreignId('payer_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // NULL when going to platform fee account
            $table->foreignId('payee_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('type', [
                'deposit',              // Client pays initial deposit
                'escrow_hold',          // Funds placed into escrow hold
                'milestone_release',    // Client confirms — funds released to freelancer
                'platform_fee',         // ProjexFlow commission deducted
                'refund',               // Funds returned to client
                'withdrawal',           // Freelancer moves wallet balance to bank
                'auto_release',         // System auto-releases after 7-day silence
            ]);

            // Always positive — direction is determined by type
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            // Stripe references for reconciliation
            $table->string('stripe_payment_intent_id', 100)->nullable();
            $table->string('stripe_transfer_id', 100)->nullable();

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
            ])->default('pending');

            $table->string('description', 300)->nullable(); // Human-readable note
            $table->timestamp('created_at')->useCurrent();  // No updated_at — append only

            $table->index(['contract_id', 'type']);
            $table->index(['payer_id', 'status']);
            $table->index(['payee_id', 'status']);
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
