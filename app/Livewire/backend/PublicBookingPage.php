<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Booking;
use App\Models\AvailabilitySchedule;
use App\Models\AvailabilityOverride;
use App\Models\Notification;
use App\Models\ServiceProfile;
use Carbon\Carbon;

#[Layout('layouts.portal')]
class PublicBookingPage extends Component
{
    public User          $provider;
    public ?ServiceProfile $profile = null;

    // Multi-step state
    public int     $step          = 1; // 1=date, 2=slot, 3=details, 4=confirm
    public string  $selectedDate  = '';
    public string  $selectedSlot  = '';
    public string  $clientName    = '';
    public string  $clientEmail   = '';
    public string  $clientMessage = '';
    public bool    $booked        = false;
    public ?int    $bookingId     = null;

    public function mount(string $username): void
    {
        $this->provider = User::where('name', $username)
            ->orWhere('slug', $username)
            ->firstOrFail();

        // Must have marketplace enabled
        abort_unless($this->provider->is_marketplace_enabled, 404);

        $this->profile = ServiceProfile::where('user_id', $this->provider->id)->first();
    }

    // ALG-CAL-02: compute free slots for a given date
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = '';
        $this->step = 2;
    }

    public function selectSlot(string $slot): void
    {
        $this->selectedSlot = $slot;
        $this->step = 3;
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
            if ($this->step === 1) $this->selectedDate = '';
            if ($this->step === 2) $this->selectedSlot = '';
        }
    }

    public function book(): void
    {
        $this->validate([
            'clientName'    => ['required', 'string', 'max:150'],
            'clientEmail'   => ['required', 'email', 'max:191'],
            'clientMessage' => ['nullable', 'string', 'max:1000'],
            'selectedDate'  => ['required', 'date'],
            'selectedSlot'  => ['required', 'date_format:H:i'],
        ]);

        // Race condition guard: re-verify slot still free
        if (! $this->isSlotFree($this->selectedDate, $this->selectedSlot)) {
            $this->addError('selectedSlot', 'This slot was just taken. Please go back and choose another time.');
            $this->step = 2;
            return;
        }

        $duration  = $this->profile?->session_duration ?? 60; // minutes
        $startAt   = Carbon::parse($this->selectedDate . ' ' . $this->selectedSlot);
        $endAt     = $startAt->copy()->addMinutes($duration);

        $booking = Booking::create([
            'provider_id'  => $this->provider->id,
            'client_id'    => auth()->id() ?? null,
            'title'        => 'Session with ' . $this->provider->name,
            'client_name'  => $this->clientName,
            'client_email' => $this->clientEmail,
            'message'      => $this->clientMessage ?: null,
            'start_at'     => $startAt,
            'end_at'       => $endAt,
        'timezone'     => 'UTC', // Add this line - or detect client timezone
            'status'       => 'pending',
        ]);

        // Notify provider
        Notification::create([
            'user_id' => $this->provider->id,
            'type'    => 'booking_request',
            'title'   => 'New booking request from ' . $this->clientName,
            'body'    => $startAt->format('M d, Y \a\t H:i'),
            'url'     => route('backend.bookingInbox'),
        ]);

        $this->booked    = true;
        $this->bookingId = $booking->id;
        $this->step      = 4;
    }

    // ── ALG-CAL-02: Available slots for a date ────────────────
    #[Computed]
    public function availableSlots(): array
    {
        if (! $this->selectedDate) return [];
        return $this->computeSlots($this->selectedDate);
    }

    public function computeSlots(string $date): array
    {
        $carbon  = Carbon::parse($date);
        $dow     = ($carbon->dayOfWeek + 6) % 7; // Mon=0

        // Step 3: Get schedule for this day
        $sched = AvailabilitySchedule::where('user_id', $this->provider->id)
            ->where('day_of_week', $dow)
            ->first();

        if (! $sched || ! $sched->is_available) return [];

        $startTime = $sched->start_time;
        $endTime   = $sched->end_time;

        // Step 4: Check override
        $override = AvailabilityOverride::where('user_id', $this->provider->id)
            ->where('date', $date)
            ->first();

        if ($override) {
            if (! $override->is_available) return [];
            $startTime = $override->start_time ?? $startTime;
            $endTime   = $override->end_time   ?? $endTime;
        }

        // Step 5: Generate candidate slots
        $duration   = $this->profile?->session_duration ?? 60;
        $candidates = [];
        $cur        = Carbon::parse($date . ' ' . $startTime);
        $stop       = Carbon::parse($date . ' ' . $endTime);

        while ($cur->copy()->addMinutes($duration) <= $stop) {
            $candidates[] = $cur->format('H:i');
            $cur->addMinutes($duration);
        }

        // Steps 6-8: Remove booked slots
        $existing = Booking::where('provider_id', $this->provider->id)
            ->whereDate('start_at', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->get(['start_at', 'end_at']);

        return array_filter($candidates, function ($slot) use ($existing, $date, $duration) {
            $slotStart = Carbon::parse($date . ' ' . $slot);
            $slotEnd   = $slotStart->copy()->addMinutes($duration);

            foreach ($existing as $b) {
                $bStart = Carbon::parse($b->start_at);
                $bEnd   = Carbon::parse($b->end_at);
                if ($bStart < $slotEnd && $bEnd > $slotStart) return false;
            }
            return true;
        });
    }

    protected function isSlotFree(string $date, string $slot): bool
    {
        $slots = $this->computeSlots($date);
        return in_array($slot, $slots);
    }

    // ── Computed: available dates for next 60 days ────────────
    #[Computed]
    public function availableDates(): array
    {
        $dates = [];
        for ($i = 1; $i <= 60; $i++) {
            $d = today()->addDays($i)->toDateString();
            if (count($this->computeSlots($d)) > 0) {
                $dates[] = $d;
            }
        }
        return $dates;
    }

    public function render()
    {
        return view('livewire.backend.publicBookingPage');
    }
}
