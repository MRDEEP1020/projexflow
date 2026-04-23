<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
#[Title('Notifications')]
class NotificationsCenter extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all | unread | read
    public string $search = '';
    public string $type   = 'all';

    public function updatingFilter(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }

    public function markAsRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        $this->dispatch('toast', ['message' => 'All marked as read.', 'type' => 'success']);
    }

    public function delete(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function deleteAll(): void
    {
        Notification::where('user_id', Auth::id())->delete();
        $this->dispatch('toast', ['message' => 'All notifications deleted.', 'type' => 'info']);
    }

    #[Computed]
    public function notifications()
    {
        return Notification::where('user_id', Auth::id())
            ->when($this->filter === 'unread', fn($q) => $q->whereNull('read_at'))
            ->when($this->filter === 'read', fn($q) => $q->whereNotNull('read_at'))
            ->when($this->type !== 'all', fn($q) => $q->where('type', $this->type))
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('body', 'like', '%' . $this->search . '%');
            }))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function counts(): array
    {
        $base = Notification::where('user_id', Auth::id());
        return [
            'all'    => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'read'   => (clone $base)->whereNotNull('read_at')->count(),
        ];
    }

    #[Computed]
    public function notificationTypes()
    {
        return [
            'all'                   => 'All',
            'booking_request'       => 'Booking requests',
            'booking_confirmed'     => 'Bookings',
            'work_submitted'        => 'Work submissions',
            'payment_released'      => 'Payments',
            'payment_auto_released' => 'Auto-releases',
            'new_review'            => 'Reviews',
            'recording_ready'       => 'Recordings',
            'transcript_ready'      => 'Transcripts',
            'github_push'           => 'GitHub',
            'contract_created'      => 'Contracts',
            'withdrawal_completed'  => 'Withdrawals',
        ];
    }

    public function render()
    {
        return view('livewire.backend.notificationsCenter');
    }
}
