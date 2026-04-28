<?php

// tests/Feature/Payments/PaymentTest.php
uses(Tests\TestCase::class);

use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\PaymentTransaction;
use App\Models\WithdrawalRequest;
use App\Models\Wallet;
use App\Models\Dispute;
use App\Livewire\Backend\ContractManager;
use App\Livewire\Backend\WalletPage;
use Livewire\Livewire;

// ── CONTRACT CREATION ─────────────────────────────────────────────

describe('Contract creation', function () {

    it('creates a contract with valid data', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('view', 'create')
            ->set('ctTitle', 'Website Redesign Project')
            ->set('ctDescription', 'Complete redesign of the company website using Laravel and Tailwind.')
            ->set('ctClientId', $freelancer->id)
            ->set('ctTotalAmount', 1000)
            ->set('ctDepositPct', 30)
            ->set('ctCurrency', 'USD')
            ->call('createContract')
            ->assertHasNoErrors();

        $contract = Contract::where('freelancer_id', $freelancer->id)
            ->where('client_id', $client->id)
            ->first();

        expect($contract)->not->toBeNull();
        expect($contract->total_amount)->toBe(1000.0);
        expect($contract->deposit_amount)->toBe(300.0);       // 30%
        expect($contract->platform_fee_amount)->toBe(100.0);  // 10%
        expect($contract->status)->toBe('draft');
    });

    it('calculates platform fee as 10% of total', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('view', 'create')
            ->set('ctTitle', 'Fee Calculation Test Contract')
            ->set('ctClientId', $freelancer->id)
            ->set('ctTotalAmount', 2000)
            ->set('ctDepositPct', 50)
            ->set('ctCurrency', 'USD')
            ->call('createContract');

        $contract = Contract::where('total_amount', 2000)->first();
        expect($contract->platform_fee_amount)->toBe(200.0); // 10% of 2000
        expect($contract->deposit_amount)->toBe(1000.0);     // 50% of 2000
    });

    it('validates milestones must sum to contract total', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('view', 'create')
            ->set('ctTitle', 'Milestone Validation Test Contract Long')
            ->set('ctClientId', $freelancer->id)
            ->set('ctTotalAmount', 1000)
            ->set('ctDepositPct', 30)
            ->set('ctMilestones', [
                ['title' => 'Phase 1', 'amount' => 400, 'due_date' => null],
                ['title' => 'Phase 2', 'amount' => 400, 'due_date' => null],
                // Total = 800, should fail (1000 expected)
            ])
            ->call('createContract')
            ->assertHasErrors(['ctMilestones']);
    });

    it('fails with missing required fields', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('view', 'create')
            ->set('ctTitle', '')
            ->call('createContract')
            ->assertHasErrors(['ctTitle']);
    });
});

// ── WORK SUBMISSION (ALG-PAY-03) ──────────────────────────────────

describe('Work submission', function () {

    it('freelancer submits work and auto_release_at is set to 7 days', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, ['status' => 'active']);

        Livewire::actingAs($freelancer)
            ->test(ContractManager::class)
            ->call('submitWork', $contract->id)
            ->assertHasNoErrors();

        $fresh = $contract->fresh();
        expect($fresh->status)->toBe('submitted');
        expect($fresh->auto_release_at)->not->toBeNull();
        expect($fresh->auto_release_at->diffInDays(now()))->toBeLessThanOrEqual(7);
    });

    it('only the freelancer can submit work', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, ['status' => 'active']);

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->call('submitWork', $contract->id)
            ->assertForbidden();
    });
});

// ── PAYMENT RELEASE (ALG-PAY-04) ──────────────────────────────────

describe('Payment release', function () {

    it('client releases payment and freelancer wallet is credited', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'       => 'submitted',
            'total_amount' => 1000,
            'deposit_amount'=> 300,
            'platform_fee_amount' => 100,
            'platform_fee_percentage' => 10,
        ]);

        walletFor($freelancer, 0);

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->call('releasePayment', $contract->id)
            ->assertHasNoErrors();

        $wallet = Wallet::where('user_id', $freelancer->id)->first();

        // Remaining = 1000 - 300 deposit = 700
        // Net = 700 - 10% fee = 630
        expect($wallet->available_balance)->toBe(630.0);
        expect($contract->fresh()->status)->toBe('completed');
    });

    it('creates platform_fee transaction on release', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'              => 'submitted',
            'total_amount'        => 500,
            'deposit_amount'      => 150,
            'platform_fee_amount' => 50,
            'platform_fee_percentage' => 10,
        ]);

        walletFor($freelancer, 0);

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->call('releasePayment', $contract->id);

        expect(PaymentTransaction::where('contract_id', $contract->id)
            ->where('type', 'platform_fee')
            ->where('amount', 35) // 10% of remaining 350
            ->exists())->toBeTrue();
    });

    it('only the client can release payment', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, ['status' => 'submitted']);

        Livewire::actingAs($freelancer)
            ->test(ContractManager::class)
            ->call('releasePayment', $contract->id)
            ->assertForbidden();
    });
});

// ── ESCROW AUTO-RELEASE (ALG-PAY-05) ──────────────────────────────

describe('Escrow auto-release', function () {

    it('auto-releases payment after 7 days with no client response', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'              => 'submitted',
            'total_amount'        => 1000,
            'deposit_amount'      => 300,
            'platform_fee_amount' => 100,
            'platform_fee_percentage' => 10,
            'auto_release_at'     => now()->subHour(), // overdue
        ]);

        walletFor($freelancer, 0);

        $this->artisan('escrow:auto-release');

        expect($contract->fresh()->status)->toBe('completed');
        expect($contract->fresh()->auto_released)->toBeTrue();

        $wallet = Wallet::where('user_id', $freelancer->id)->first();
        expect($wallet->available_balance)->toBeGreaterThan(0);
    });

    it('does not release contracts that are not yet overdue', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'          => 'submitted',
            'auto_release_at' => now()->addDays(3), // not yet due
        ]);

        $this->artisan('escrow:auto-release');

        expect($contract->fresh()->status)->toBe('submitted');
    });
});

// ── DISPUTES (ALG-PAY-06) ────────────────────────────────────────

describe('Disputes', function () {

    it('opens a dispute and freezes the contract', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, ['status' => 'submitted']);

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('openContractId', $contract->id)
            ->set('view', 'detail')
            ->set('showDisputeForm', true)
            ->set('disputeReason', 'work_not_delivered')
            ->set('disputeDescription', 'The freelancer did not deliver the agreed scope of work and has stopped responding to messages. The deadline was missed by 2 weeks.')
            ->call('openDispute', $contract->id)
            ->assertHasNoErrors();

        expect($contract->fresh()->status)->toBe('disputed');
        expect(Dispute::where('contract_id', $contract->id)->exists())->toBeTrue();
    });

    it('fails to open dispute with short description', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, ['status' => 'submitted']);

        Livewire::actingAs($client)
            ->test(ContractManager::class)
            ->set('openContractId', $contract->id)
            ->set('view', 'detail')
            ->set('disputeReason', 'quality_issues')
            ->set('disputeDescription', 'Too short')
            ->call('openDispute', $contract->id)
            ->assertHasErrors(['disputeDescription']);
    });
});

// ── WALLET + WITHDRAWALS (ALG-PAY-08) ────────────────────────────

describe('Wallet and withdrawals', function () {

    it('shows available balance in wallet', function () {
        $freelancer = freelancerUser();
        walletFor($freelancer, 750.00);

        Livewire::actingAs($freelancer)
            ->test(WalletPage::class)
            ->assertSee('750.00');
    });

    it('submits a mobile money withdrawal request', function () {
        $freelancer = freelancerUser();
        walletFor($freelancer, 500);

        Livewire::actingAs($freelancer)
            ->test(WalletPage::class)
            ->set('method', 'mobile_money')
            ->set('amount', 200)
            ->set('currency', 'XAF')
            ->set('mmPhone', '+237600000000')
            ->set('mmOperator', 'mtn')
            ->set('mmCountry', 'CM')
            ->call('requestWithdrawal')
            ->assertHasNoErrors();

        expect(WithdrawalRequest::where('user_id', $freelancer->id)
            ->where('amount', 200)
            ->where('method', 'mobile_money')
            ->exists())->toBeTrue();

        // Balance should be deducted immediately
        $wallet = Wallet::where('user_id', $freelancer->id)->first();
        expect($wallet->available_balance)->toBe(300.0);
    });

    it('cannot withdraw more than available balance', function () {
        $freelancer = freelancerUser();
        walletFor($freelancer, 100);

        Livewire::actingAs($freelancer)
            ->test(WalletPage::class)
            ->set('method', 'mobile_money')
            ->set('amount', 999)
            ->set('mmPhone', '+237600000000')
            ->set('mmOperator', 'mtn')
            ->set('mmCountry', 'CM')
            ->call('requestWithdrawal')
            ->assertHasErrors(['amount']);
    });

    it('cannot withdraw zero or negative amount', function () {
        $freelancer = freelancerUser();
        walletFor($freelancer, 500);

        Livewire::actingAs($freelancer)
            ->test(WalletPage::class)
            ->set('amount', 0)
            ->call('requestWithdrawal')
            ->assertHasErrors(['amount']);
    });
});
