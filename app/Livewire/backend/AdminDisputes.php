<?php
// ══════════════════════════════════════════════════════════════════
// app/Livewire/Admin/AdminDisputes.php
// ══════════════════════════════════════════════════════════════════

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Dispute;
use App\Models\Contract;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
#[Title('Disputes')]
class AdminDisputes extends Component
{
    public string $filter    = 'open';
    public ?int   $viewId    = null;
    public string $resolution= '';
    public string $resolveFor= ''; // 'client' | 'freelancer' | 'split'
    public float  $splitPct  = 50; // freelancer gets X%

    public function resolve(int $disputeId): void
    {
        $this->validate([
            'resolution' => ['required','string','min:20','max:2000'],
            'resolveFor' => ['required','in:client,freelancer,split'],
            'splitPct'   => ['required_if:resolveFor,split','numeric','min:0','max:100'],
        ]);

        $dispute  = Dispute::with('contract')->findOrFail($disputeId);
        $contract = $dispute->contract;

        DB::transaction(function () use ($dispute, $contract) {
            $totalHeld = $contract->total_amount;
            $fee       = $contract->platform_fee_amount;

            match ($this->resolveFor) {
                'freelancer' => $this->releaseTo($contract, $contract->freelancer_id, $totalHeld - $fee),
                'client'     => $this->refundTo($contract, $contract->client_id, $totalHeld),
                'split'      => $this->splitRelease($contract, $this->splitPct),
            };

            $dispute->update([
                'status'      => 'resolved',
                'resolution'  => $this->resolution,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);
            $contract->update(['status' => 'completed']);
        });

        $this->viewId     = null;
        $this->resolution = '';
        $this->resolveFor = '';
        $this->dispatch('toast', ['message' => 'Dispute resolved.', 'type' => 'success']);
    }

    protected function releaseTo(Contract $contract, int $userId, float $amount): void
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        $wallet->increment('available_balance', $amount);
        $wallet->increment('total_earned', $amount);

        Notification::create([
            'user_id' => $userId,
            'type'    => 'dispute_resolved',
            'title'   => 'Dispute resolved in your favor',
            'body'    => '$'.number_format($amount).' has been added to your wallet.',
            'url'     => route('backend.wallet'),
        ]);
    }

    protected function refundTo(Contract $contract, int $clientId, float $amount): void
    {
        Notification::create([
            'user_id' => $clientId,
            'type'    => 'dispute_refunded',
            'title'   => 'Dispute resolved — refund issued',
            'body'    => '$'.number_format($amount).' will be refunded to your payment method.',
            'url'     => route('backend.contracts'),
        ]);
    }

    protected function splitRelease(Contract $contract, float $freelancerPct): void
    {
        $total          = $contract->total_amount - $contract->platform_fee_amount;
        $freelancerAmt  = round($total * ($freelancerPct / 100), 2);
        $clientAmt      = $total - $freelancerAmt;

        $this->releaseTo($contract, $contract->freelancer_id, $freelancerAmt);
        $this->refundTo($contract, $contract->client_id, $clientAmt);
    }

    #[Computed]
    public function disputes()
    {
        return Dispute::when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->with(['contract.client','contract.freelancer'])
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function openDispute(): ?Dispute
    {
        if (! $this->viewId) return null;
        return Dispute::with(['contract.client','contract.freelancer','raisedBy'])->find($this->viewId);
    }

    public function render() { return view('livewire.backend.adminDisputes'); }
}
