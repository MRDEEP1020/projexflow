<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Booking;
use App\Models\MeetingRoom;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Bookings')]
class BookingInbox extends Component
{
    public string  $tab         = 'pending'; // pending | confirmed | past
    public ?int    $detailId    = null;       // open detail panel
    public string  $declineNote = '';

    protected function getListeners(): array
    {
        return [
            'echo-private:user.' . Auth::id() . ',.booking.request' => '$refresh',
        ];
    }

    // ── Actions ───────────────────────────────────────────────
    public function confirm(int $bookingId): void
    {
        $booking = Booking::where('id', $bookingId)
            ->where('provider_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        // ALG-CAL-03: Re-check slot is still free
        $component = new PublicBookingPage();
        $component->mount($this->providerUser()->name ?? '');
        $date = Carbon::parse($booking->start_at)->toDateString();
        $slot = Carbon::parse($booking->start_at)->format('H:i');
        $free = $component->computeSlots($date);

        if (! in_array($slot, $free)) {
            $this->dispatch('toast', [
                'message' => 'This slot is no longer available. Cannot confirm.',
                'type'    => 'error',
            ]);
            return;
        }

        // Create meeting room
        $room = MeetingRoom::create([
            'booking_id'  => $booking->id,
            'created_by'  => Auth::id(),
            'title'       => $booking->title,
            'room_token'  => 'room-' . Str::random(32),
            'status'      => 'scheduled',
        ]);

        $booking->update([
            'status'          => 'confirmed',
            'meeting_room_id' => $room->id,
        ]);

        // Notify client if registered
        if ($booking->client_id) {
            Notification::create([
                'user_id' => $booking->client_id,
                'type'    => 'booking_confirmed',
                'title'   => 'Your booking is confirmed!',
                'body'    => Carbon::parse($booking->start_at)->format('M d, Y \a\t H:i'),
                'url'     => route('backend.bookingInbox'),
            ]);
        }

        $this->dispatch('toast', ['message' => 'Booking confirmed. Meeting room created.', 'type' => 'success']);
    }

    public function decline(int $bookingId): void
    {
        $booking = Booking::where('id', $bookingId)
            ->where('provider_id', Auth::id())
            ->whereIn('status', ['pending'])
            ->firstOrFail();

        $booking->update(['status' => 'cancelled']);

        if ($booking->client_id) {
            Notification::create([
                'user_id' => $booking->client_id,
                'type'    => 'booking_cancelled',
                'title'   => 'Booking declined',
                'body'    => $this->declineNote ?: 'The provider has declined your booking request.',
                'url'     => route('backend.bookingInbox'),
            ]);
        }

        $this->declineNote = '';
        $this->detailId    = null;
        $this->dispatch('toast', ['message' => 'Booking declined.', 'type' => 'info']);
    }

    public function openDetail(int $id): void { $this->detailId = $id; }
    public function closeDetail(): void       { $this->detailId = null; }

    protected function providerUser()
    {
        return Auth::user();
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function bookings()
    {
        $userId = Auth::id();

        return Booking::where(function ($q) use ($userId) {
                $q->where('provider_id', $userId)
                  ->orWhere('client_id', $userId);
            })
            ->when($this->tab === 'pending',   fn ($q) => $q->where('status', 'pending'))
            ->when($this->tab === 'confirmed', fn ($q) => $q->where('status', 'confirmed')->where('start_at', '>=', now()))
            ->when($this->tab === 'past',      fn ($q) => $q->whereIn('status', ['confirmed','cancelled'])->where('start_at', '<', now()))
            ->with(['provider', 'meetingRoom.recordings'])
            ->orderBy('start_at', $this->tab === 'past' ? 'desc' : 'asc')
            ->get();
    }

    #[Computed]
    public function counts(): array
    {
        $uid = Auth::id();
        $base = Booking::where(fn ($q) => $q->where('provider_id', $uid)->orWhere('client_id', $uid));
        return [
            'pending'   => (clone $base)->where('status', 'pending')->count(),
            'confirmed' => (clone $base)->where('status', 'confirmed')->where('start_at', '>=', now())->count(),
            'past'      => (clone $base)->whereIn('status', ['confirmed','cancelled'])->where('start_at', '<', now())->count(),
        ];
    }

    #[Computed]
    public function openBooking(): ?Booking
    {
        if (! $this->detailId) return null;
        return Booking::with(['provider', 'meetingRoom.recordings'])->find($this->detailId);
    }

    public function render()
    {
        return view('livewire.backend.bookingInbox');
    }
}
