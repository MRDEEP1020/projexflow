<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\AvailabilitySchedule;
use App\Models\AvailabilityOverride;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Availability Settings')]
class AvailabilitySettings extends Component
{
    // Weekly schedule: [day_of_week => ['available','start','end']]
    public array  $schedule      = [];
    public int    $slotMinutes   = 30;

    // Override form
    public string $overrideDate      = '';
    public bool   $overrideAvailable = false;
    public string $overrideReason    = '';
    public string $overrideStart     = '09:00';
    public string $overrideEnd       = '17:00';

    public function mount(): void
    {
        $this->loadSchedule();
    }

    protected function loadSchedule(): void
    {
        // Default schedule: Mon–Fri 09:00–17:00, Sat–Sun closed
        $defaults = [];
        for ($i = 0; $i < 7; $i++) {
            $defaults[$i] = [
                'available'  => $i < 5,
                'start_time' => '09:00',
                'end_time'   => '17:00',
            ];
        }

        $saved = AvailabilitySchedule::where('user_id', Auth::id())
            ->get()
            ->keyBy('day_of_week')
            ->toArray();

        $this->schedule = [];
        for ($i = 0; $i < 7; $i++) {
            $row = $saved[$i] ?? null;
            $this->schedule[$i] = [
                'available'  => $row ? (bool) $row['is_available'] : $defaults[$i]['available'],
                'start_time' => $row['start_time'] ?? $defaults[$i]['start_time'],
                'end_time'   => $row['end_time']   ?? $defaults[$i]['end_time'],
            ];
        }
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time'   => ['required', 'date_format:H:i'],
        ]);

        foreach ($this->schedule as $day => $row) {
            AvailabilitySchedule::updateOrCreate(
                ['user_id' => Auth::id(), 'day_of_week' => $day],
                [
                    'is_available' => $row['available'],
                    'start_time'   => $row['start_time'],
                    'end_time'     => $row['end_time'],
                ]
            );
        }

        $this->dispatch('toast', ['message' => 'Availability schedule saved.', 'type' => 'success']);
    }

    public function addOverride(): void
    {
        $this->validate([
            'overrideDate'  => ['required', 'date', 'after_or_equal:today'],
            'overrideStart' => ['required_if:overrideAvailable,true', 'nullable', 'date_format:H:i'],
            'overrideEnd'   => ['required_if:overrideAvailable,true', 'nullable', 'date_format:H:i', 'after:overrideStart'],
            'overrideReason'=> ['nullable', 'string', 'max:200'],
        ]);

        AvailabilityOverride::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => $this->overrideDate],
            [
                'is_available' => $this->overrideAvailable,
                'start_time'   => $this->overrideAvailable ? $this->overrideStart : null,
                'end_time'     => $this->overrideAvailable ? $this->overrideEnd   : null,
                'reason'       => $this->overrideReason ?: null,
            ]
        );

        $this->overrideDate      = '';
        $this->overrideAvailable = false;
        $this->overrideReason    = '';
        $this->dispatch('toast', ['message' => 'Override added.', 'type' => 'success']);
    }

    public function deleteOverride(int $id): void
    {
        AvailabilityOverride::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
        $this->dispatch('toast', ['message' => 'Override removed.', 'type' => 'info']);
    }

    #[Computed]
    public function upcomingOverrides()
    {
        return AvailabilityOverride::where('user_id', Auth::id())
            ->where('date', '>=', today())
            ->orderBy('date')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function previewSlots(): array
    {
        // Show next 7 days slot preview
        $preview = [];
        for ($i = 0; $i < 7; $i++) {
            $date = today()->addDays($i);
            $dow  = $date->dayOfWeek; // 0=Sun
            // Convert: Carbon Sun=0, we store Mon=0
            $dow = ($dow + 6) % 7;

            $row      = $this->schedule[$dow] ?? null;
            $override = AvailabilityOverride::where('user_id', Auth::id())
                ->where('date', $date->toDateString())
                ->first();

            if ($override) {
                $available = $override->is_available;
                $slots     = $available ? $this->generateSlots($override->start_time, $override->end_time) : [];
            } else {
                $available = $row['available'] ?? false;
                $slots     = $available ? $this->generateSlots($row['start_time'], $row['end_time']) : [];
            }

            $preview[] = [
                'date'      => $date->format('D M j'),
                'available' => $available,
                'count'     => count($slots),
            ];
        }
        return $preview;
    }

    protected function generateSlots(string $start, string $end): array
    {
        $slots = [];
        $cur   = Carbon::createFromFormat('H:i', $start);
        $stop  = Carbon::createFromFormat('H:i', $end);
        while ($cur < $stop) {
            $slots[] = $cur->format('H:i');
            $cur->addMinutes($this->slotMinutes);
        }
        return $slots;
    }

    public function render()
    {
        return view('livewire.backend.availabilitySettings');
    }
}
