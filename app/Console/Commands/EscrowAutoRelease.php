<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EscrowAutoRelease extends Command
{
    protected $signature = 'escrow:auto-release';
    protected $description = 'Release escrowed payments after 7 days of work submission (ALG-PAY-05)';

    public function handle()
    {
        // Find contracts where:
        // 1. Status is 'submitted'
        // 2. auto_release_at is <= now
        // 3. No active dispute

        $contracts = Contract::where('status', 'submitted')
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<=', now())
            ->whereNull('disputed_at')
            ->get();

        $this->info("Found {$contracts->count()} contracts eligible for auto-release");

        foreach ($contracts as $contract) {
            try {
                DB::transaction(function () use ($contract) {
                    // ALG-PAY-05 Step 3: Release remaining balance
                    $remaining    = $contract->total_amount - $contract->deposit_amount;
                    $platformFee  = round($remaining * $contract->platform_fee_percentage / 100, 2);
                    $freelancerNet = $remaining - $platformFee;

                    // Record transactions
                    PaymentTransaction::create([
                        'contract_id' => $contract->id,
                        'type'        => 'milestone_release',
                        'amount'      => $remaining,
                        'payer_id'    => $contract->client_id,
                        'payee_id'    => $contract->freelancer_id,
                        'status'      => 'completed',
                        'currency'    => $contract->currency,
                    ]);
                    PaymentTransaction::create([
                        'contract_id' => $contract->id,
                        'type'        => 'platform_fee',
                        'amount'      => $platformFee,
                        'payer_id'    => $contract->freelancer_id,
                        'payee_id'    => null,
                        'status'      => 'completed',
                        'currency'    => $contract->currency,
                    ]);

                    // Update wallet
                    $wallet = Wallet::firstOrCreate(['user_id' => $contract->freelancer_id]);
                    $wallet->increment('available_balance', $freelancerNet);
                    $wallet->increment('total_earned', $freelancerNet);
                    $wallet->decrement('held_balance', $contract->deposit_amount + $remaining);

                    // Update contract
                    $contract->update([
                        'status'        => 'completed',
                        'completed_at'  => now(),
                        'auto_released' => true,
                    ]);

                    // Notify both parties
                    Notification::create([
                        'user_id' => $contract->freelancer_id,
                        'type'    => 'payment_auto_released',
                        'title'   => 'Payment automatically released!',
                        'body'    => 'No response from client. Your ' . $contract->currency . ' ' . 
                                     number_format($freelancerNet) . ' is now in your wallet.',
                        'url'     => route('backend.wallet'),
                    ]);

                    Notification::create([
                        'user_id' => $contract->client_id,
                        'type'    => 'contract_auto_completed',
                        'title'   => 'Contract auto-completed: ' . $contract->title,
                        'body'    => 'Payment released to freelancer after 7 days.',
                        'url'     => route('backend.contracts'),
                    ]);

                    Log::info('Contract auto-released', [
                        'contract_id'   => $contract->id,
                        'freelancer_id' => $contract->freelancer_id,
                        'amount'        => $freelancerNet,
                    ]);

                    $this->line("✓ Contract #{$contract->id} auto-released");
                });
            } catch (\Throwable $e) {
                Log::error('Auto-release failed for contract', [
                    'contract_id' => $contract->id,
                    'error'       => $e->getMessage(),
                ]);
                $this->error("✗ Contract #{$contract->id} failed: {$e->getMessage()}");
            }
        }

        $this->info('Auto-release job completed');
        return 0;
    }
}
