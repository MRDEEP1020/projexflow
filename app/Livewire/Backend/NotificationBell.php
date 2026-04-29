<?php

namespace App\Livewire\Backend;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadCount();
    }

    protected function getListeners(): array
    {
        return [
            'echo-private:user.' . Auth::id() . ',.notification.created' => 'handleNewNotification',
        ];
    }

    public function handleNewNotification(array $data): void
    {
        $this->unreadCount++;
    }

    public function markAllRead(): void
    {
        Auth::user()
            ->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->unreadCount = 0;
    }

    public function markRead(int $id): void
    {
        $n = Notification::where('id', $id)
                         ->where('user_id', Auth::id())
                         ->first();

        if ($n && $n->isUnread()) {
            $n->markAsRead();
            $this->unreadCount = max(0, $this->unreadCount - 1);
        }
    }

    protected function loadCount(): void
    {
        $this->unreadCount = Auth::user()
                                 ->appNotifications()
                                 ->whereNull('read_at')
                                 ->count();
    }

    public function render()
    {
        $notifications = Auth::user()
                             ->appNotifications()
                             ->latest('created_at')
                             ->limit(8)
                             ->get();

        return view('livewire.backend.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}
