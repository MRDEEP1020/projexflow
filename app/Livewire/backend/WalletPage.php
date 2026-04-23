<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Models\PaymentTransaction;
use App\Jobs\ProcessWithdrawal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
#[Title('Wallet')]
class WalletPage extends Component
{
    public string  $method       = 'mobile_money';
    public float   $amount       = 0;
    public string  $currency     = 'XAF';

    // Mobile money fields
    public string  $mmPhone      = '';
    public string  $mmOperator   = 'mtn';
    public string  $mmCountry    = 'CM';

    // Bank fields
    public string  $bankAccount  = '';
    public string  $bankName     = '';
    public string  $bankSwift    = '';

    public string  $txFilter     = 'all';

    // ── ALG-PAY-08: Request withdrawal ────────────────────────
    public function requestWithdrawal(): void
    {
        $wallet = Wallet::where('user_id', Auth::id())->firstOrFail();

        $this->validate([
            'amount'   => ['required','numeric','min:1','max:' . $wallet->available_balance],
            'currency' => ['required','string'],
            'method'   => ['required','in:mobile_money,bank,stripe'],
        ]);

        // KYC check
        if ($this->method === 'stripe' && ! Auth::user()->stripe_connect_id) {
            $this->addError('method', 'Complete identity verification before withdrawing via Stripe.');
            return;
        }

        // Build account_details based on method
        $accountDetails = match ($this->method) {
            'mobile_money' => [
                'phone'    => $this->mmPhone,
                'operator' => $this->mmOperator,
                'country'  => $this->mmCountry,
            ],
            'bank' => [
                'account_number' => $this->bankAccount,
                'bank_name'      => $this->bankName,
                'swift'          => $this->bankSwift,
            ],
            default => ['stripe_connect_id' => Auth::user()->stripe_connect_id],
        };

        DB::transaction(function () use ($wallet, $accountDetails) {
            $wallet->decrement('available_balance', $this->amount);

            $req = WithdrawalRequest::create([
                'user_id'        => Auth::id(),
                'amount'         => $this->amount,
                'currency'       => $this->currency,
                'method'         => $this->method,
                'account_details'=> $accountDetails, // cast as encrypted:array in model
                'status'         => 'pending',
            ]);

            ProcessWithdrawal::dispatch($req);
        });

        $this->amount = 0;
        $this->dispatch('toast', ['message' => 'Withdrawal request submitted!', 'type' => 'success']);
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function wallet(): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => Auth::id()],
            ['available_balance' => 0, 'held_balance' => 0, 'total_earned' => 0]
        );
    }

    #[Computed]
    public function transactions()
    {
        return PaymentTransaction::where(fn($q) =>
                $q->where('payer_id', Auth::id())->orWhere('payee_id', Auth::id())
            )
            ->when($this->txFilter !== 'all', fn($q) => $q->where('type', $this->txFilter))
            ->with('contract')
            ->latest()
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function pendingWithdrawals()
    {
        return WithdrawalRequest::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.backend.walletPage');
    }
}
