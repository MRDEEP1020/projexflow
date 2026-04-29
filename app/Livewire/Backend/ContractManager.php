<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\Dispute;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app')]
#[Title('Contracts')]
class ContractManager extends Component
{
    public string $view = 'list'; // list | create | detail

    // ── Create form ───────────────────────────────────────────
    public string  $ctTitle             = '';
    public string  $ctDescription       = '';
    public int|string $ctClientId       = '';
    public float   $ctTotalAmount       = 0;
    public int     $ctDepositPct        = 30;
    public string  $ctCurrency          = 'USD';
    public array   $ctMilestones        = [];
    public string  $ctNewMsTitle        = '';
    public float   $ctNewMsAmount       = 0;
    public string  $ctNewMsDue          = '';

    // ── Detail view ───────────────────────────────────────────
    public ?int    $openContractId      = null;
    public string  $disputeReason       = '';
    public string  $disputeDescription  = '';
    public bool    $showDisputeForm     = false;
    public string  $filterStatus        = 'all';

    protected function getListeners(): array
    {
        return [
            'echo-private:contract.' . ($this->openContractId ?? 0) . ',.payment.deposited' => '$refresh',
            'echo-private:contract.' . ($this->openContractId ?? 0) . ',.payment.released'  => '$refresh',
            'echo-private:contract.' . ($this->openContractId ?? 0) . ',.work.submitted'    => '$refresh',
        ];
    }

    // ── Create ────────────────────────────────────────────────
    public function addMilestone(): void
    {
        $this->validate([
            'ctNewMsTitle'  => ['required','string','max:200'],
            'ctNewMsAmount' => ['required','numeric','min:1'],
            'ctNewMsDue'    => ['nullable','date'],
        ]);

        $this->ctMilestones[] = [
            'title'    => $this->ctNewMsTitle,
            'amount'   => $this->ctNewMsAmount,
            'due_date' => $this->ctNewMsDue ?: null,
        ];

        $this->ctNewMsTitle  = '';
        $this->ctNewMsAmount = 0;
        $this->ctNewMsDue    = '';
    }

    public function removeMilestone(int $i): void
    {
        array_splice($this->ctMilestones, $i, 1);
    }

    public function getMsTotal(): float
    {
        return array_sum(array_column($this->ctMilestones, 'amount'));
    }

    public function createContract(): void
    {
        $this->validate([
            'ctTitle'       => ['required','string','max:255'],
            'ctClientId'    => ['required','integer','exists:users,id'],
            'ctTotalAmount' => ['required','numeric','min:1'],
            'ctDepositPct'  => ['required','integer','min:1','max:100'],
            'ctCurrency'    => ['required','string'],
        ]);

        if (count($this->ctMilestones) > 0) {
            $msSum = $this->getMsTotal();
            if (abs($msSum - $this->ctTotalAmount) > 0.01) {
                $this->addError('ctMilestones', "Milestone total ({$this->ctCurrency} {$msSum}) must equal contract total ({$this->ctTotalAmount}).");
                return;
            }
        }

        $depositAmt    = round($this->ctTotalAmount * $this->ctDepositPct / 100, 2);
        $platformFeePct = config('projexflow.platform_fee_pct', 10);
        $platformFeeAmt = round($this->ctTotalAmount * $platformFeePct / 100, 2);

        DB::transaction(function () use ($depositAmt, $platformFeePct, $platformFeeAmt) {
            $contract = Contract::create([
                'freelancer_id'          => Auth::id(),
                'client_id'              => $this->ctClientId,
                'title'                  => $this->ctTitle,
                'description'            => $this->ctDescription ?: null,
                'total_amount'           => $this->ctTotalAmount,
                'deposit_percentage'     => $this->ctDepositPct,
                'deposit_amount'         => $depositAmt,
                'platform_fee_percentage'=> $platformFeePct,
                'platform_fee_amount'    => $platformFeeAmt,
                'currency'               => $this->ctCurrency,
                'status'                 => 'draft',
            ]);

            foreach ($this->ctMilestones as $ms) {
                ContractMilestone::create([
                    'contract_id' => $contract->id,
                    'title'       => $ms['title'],
                    'amount'      => $ms['amount'],
                    'due_date'    => $ms['due_date'],
                    'status'      => 'pending',
                ]);
            }

            // Notify client
            Notification::create([
                'user_id' => $this->ctClientId,
                'type'    => 'contract_created',
                'title'   => Auth::user()->name . ' sent you a contract',
                'body'    => $this->ctTitle . ' · ' . $this->ctCurrency . ' ' . number_format($this->ctTotalAmount),
                'url'     => route('backend.contracts'),
            ]);

            $this->openContractId = $contract->id;
        });

        $this->dispatch('toast', ['message' => 'Contract created!', 'type' => 'success']);
        $this->view = 'detail';
    }

    // ── Work submission (ALG-PAY-03) ──────────────────────────
    public function submitWork(int $contractId): void
    {
        $contract = Contract::where('id', $contractId)
            ->where('freelancer_id', Auth::id())
            ->where('status', 'active')
            ->firstOrFail();

        $contract->update([
            'status'          => 'submitted',
            'work_submitted_at'=> now(),
            'auto_release_at' => now()->addDays(7),
        ]);

        Notification::create([
            'user_id' => $contract->client_id,
            'type'    => 'work_submitted',
            'title'   => 'Work submitted for review',
            'body'    => $contract->title . ' — you have 7 days to approve or request changes.',
            'url'     => route('backend.contracts'),
        ]);

        $this->dispatch('toast', ['message' => 'Work submitted. Client has 7 days to approve.', 'type' => 'success']);
    }

    // ── Payment release (ALG-PAY-04) ─────────────────────────
    public function releasePayment(int $contractId): void
    {
        $contract = Contract::where('id', $contractId)
            ->where('client_id', Auth::id())
            ->where('status', 'submitted')
            ->firstOrFail();

        $remaining    = $contract->total_amount - $contract->deposit_amount;
        $platformFee  = round($remaining * $contract->platform_fee_percentage / 100, 2);
        $freelancerNet = $remaining - $platformFee;

        DB::transaction(function () use ($contract, $remaining, $platformFee, $freelancerNet) {
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
            $contract->update(['status' => 'completed', 'completed_at' => now()]);
        });

        Notification::create([
            'user_id' => $contract->freelancer_id,
            'type'    => 'payment_released',
            'title'   => 'Payment released!',
            'body'    => $contract->currency . ' ' . number_format($freelancerNet) . ' is now in your wallet.',
            'url'     => route('backend.wallet'),
        ]);

        $this->dispatch('toast', ['message' => 'Payment released to freelancer!', 'type' => 'success']);
    }

    // ── Dispute (ALG-PAY-06) ──────────────────────────────────
    public function openDispute(int $contractId): void
    {
        $this->validate([
            'disputeReason'      => ['required','string'],
            'disputeDescription' => ['required','string','min:50','max:3000'],
        ]);

        $contract = Contract::where('id', $contractId)
            ->where(fn($q) => $q->where('client_id', Auth::id())->orWhere('freelancer_id', Auth::id()))
            ->whereIn('status', ['active','submitted'])
            ->firstOrFail();

        DB::transaction(function () use ($contract) {
            $contract->update(['status' => 'disputed']);
            Dispute::create([
                'contract_id'  => $contract->id,
                'raised_by'    => Auth::id(),
                'against'      => $contract->client_id === Auth::id()
                    ? $contract->freelancer_id
                    : $contract->client_id,
                'reason'       => $this->disputeReason,
                'description'  => $this->disputeDescription,
                'status'       => 'open',
            ]);
        });

        $this->disputeReason      = '';
        $this->disputeDescription = '';
        $this->showDisputeForm    = false;
        $this->dispatch('toast', ['message' => 'Dispute opened. Funds are frozen.', 'type' => 'info']);
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function contracts()
    {
        $uid = Auth::id();
        return Contract::where(fn($q) => $q->where('client_id', $uid)->orWhere('freelancer_id', $uid))
            ->when($this->filterStatus !== 'all', fn($q) => $q->where('status', $this->filterStatus))
            ->with(['client','freelancer'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function openContract(): ?Contract
    {
        if (! $this->openContractId) return null;
        return Contract::with(['client','freelancer','milestones','transactions','dispute'])->find($this->openContractId);
    }

    #[Computed]
    public function contractCounts(): array
    {
        $uid = Auth::id();
        $base = Contract::where(fn($q) => $q->where('client_id', $uid)->orWhere('freelancer_id', $uid));
        return [
            'all'       => (clone $base)->count(),
            'draft'     => (clone $base)->where('status','draft')->count(),
            'active'    => (clone $base)->where('status','active')->count(),
            'submitted' => (clone $base)->where('status','submitted')->count(),
            'completed' => (clone $base)->where('status','completed')->count(),
            'disputed'  => (clone $base)->where('status','disputed')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.backend.contractManager');
    }
}
