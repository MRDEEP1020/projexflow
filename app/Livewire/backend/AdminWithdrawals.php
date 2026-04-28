<?php
// ══════════════════════════════════════════════════════════════════
// app/Livewire/Backend/AdminWithdrawals.php
// ══════════════════════════════════════════════════════════════════

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\WithdrawalRequest;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
#[Title('Withdrawals')]
class AdminWithdrawals extends Component
{
    use WithPagination;

    public string $filter   = 'pending';
    public string $method   = 'all';
    public ?int   $viewId   = null;
    public string $failNote = '';

    // Manual approve — for withdrawals that need admin sign-off
    // (normally handled by queue, but admin can force-approve edge cases)
    public function approve(int $id): void
    {
        $req = WithdrawalRequest::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Dispatch job manually
        \App\Jobs\ProcessWithdrawal::dispatch($req);

        $this->dispatch('toast', ['message' => 'Withdrawal queued for processing.', 'type' => 'success']);
    }

    // Admin rejection — returns funds to wallet
    public function reject(int $id): void
    {
        $this->validate(['failNote' => ['required','string','min:10']]);

        DB::transaction(function () use ($id) {
            $req = WithdrawalRequest::findOrFail($id);
            $req->update(['status' => 'failed', 'failure_reason' => $this->failNote]);

            // Refund to wallet
            $wallet = Wallet::where('user_id', $req->user_id)->first();
            if ($wallet) {
                $wallet->increment('available_balance', $req->amount);
            }

            Notification::create([
                'user_id' => $req->user_id,
                'type'    => 'withdrawal_failed',
                'title'   => 'Withdrawal request rejected',
                'body'    => $this->failNote,
                'url'     => route('backend.wallet'),
            ]);
        });

        $this->failNote = '';
        $this->viewId = null;
        $this->dispatch('toast', ['message' => 'Withdrawal rejected. Funds returned to wallet.', 'type' => 'info']);
    }

    #[Computed]
    public function withdrawals()
    {
        return WithdrawalRequest::with('user')
            ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->when($this->method !== 'all', fn($q) => $q->where('method', $this->method))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function totals(): array
    {
        return [
            'pending'   => WithdrawalRequest::where('status','pending')->sum('amount'),
            'completed' => WithdrawalRequest::where('status','completed')->sum('amount'),
            'failed'    => WithdrawalRequest::where('status','failed')->count(),
        ];
    }

    #[Computed]
    public function openRequest(): ?WithdrawalRequest
    {
        return $this->viewId
            ? WithdrawalRequest::with('user')->find($this->viewId)
            : null;
    }

    public function render() { return view('livewire.backend.adminWithdrawals'); }
}
