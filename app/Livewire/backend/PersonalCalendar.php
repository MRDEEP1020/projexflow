<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Task;
use App\Models\Milestone;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Layout('layouts.app')]
#[Title('Calendar')]
class PersonalCalendar extends Component
{
    public string  $view        = 'week'; // week | month | day
    public string  $currentDate;          // Y-m-d anchor
    public ?int    $openTaskId  = null;
    public ?int    $openBooking = null;

    public function mount(): void
    {
        $this->currentDate = today()->toDateString();
    }

    // ── Navigation ────────────────────────────────────────────
    public function prev(): void
    {
        $this->currentDate = match ($this->view) {
            'month' => Carbon::parse($this->currentDate)->subMonth()->toDateString(),
            'day'   => Carbon::parse($this->currentDate)->subDay()->toDateString(),
            default => Carbon::parse($this->currentDate)->subWeek()->toDateString(),
        };
    }

    public function next(): void
    {
        $this->currentDate = match ($this->view) {
            'month' => Carbon::parse($this->currentDate)->addMonth()->toDateString(),
            'day'   => Carbon::parse($this->currentDate)->addDay()->toDateString(),
            default => Carbon::parse($this->currentDate)->addWeek()->toDateString(),
        };
    }

    public function goToday(): void
    {
        $this->currentDate = today()->toDateString();
    }

    public function setView(string $v): void
    {
        $this->view = $v;
    }

    public function openTask(int $id): void  { $this->openTaskId = $id; }
    public function closeTask(): void        { $this->openTaskId = null; }

    // ── Date range helpers ────────────────────────────────────
    protected function getRange(): array
    {
        $anchor = Carbon::parse($this->currentDate);

        return match ($this->view) {
            'month' => [
                $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
            'day' => [
                $anchor->copy()->startOfDay(),
                $anchor->copy()->endOfDay(),
            ],
            default => [ // week
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY),
            ],
        };
    }

    // ── Computed: merged events ───────────────────────────────
    #[Computed]
    public function events(): array
    {
        [$start, $end] = $this->getRange();
        $user  = Auth::user();
        $orgId = Session::get('active_org_id');

        // --- Tasks due in range ---
        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done'])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->with('project:id,name')
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'type'     => 'task',
                'title'    => $t->title,
                'date'     => $t->due_date->toDateString(),
                'time'     => null,
                'color'    => match ($t->priority) {
                    'critical' => '#ef4444',
                    'high'     => '#f97316',
                    'medium'   => '#f59e0b',
                    default    => '#60a5fa',
                },
                'meta'     => $t->project->name ?? 'Personal',
                'overdue'  => $t->due_date->isPast(),
                'model_id' => $t->id,
            ]);

        // --- Milestones due in range ---
        $projectIds = \App\Models\ProjectMember::where('user_id', $user->id)
            ->pluck('project_id');

        $milestones = Milestone::whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->with('project:id,name')
            ->get()
            ->map(fn ($m) => [
                'id'       => 'ms-' . $m->id,
                'type'     => 'milestone',
                'title'    => $m->name,
                'date'     => $m->due_date->toDateString(),
                'time'     => null,
                'color'    => '#7EE8A2',
                'meta'     => $m->project->name ?? '',
                'overdue'  => $m->due_date->isPast(),
                'model_id' => $m->id,
            ]);

        // --- Bookings in range ---
        $bookings = Booking::where(function ($q) use ($user) {
                $q->where('provider_id', $user->id)
                  ->orWhere('client_id', $user->id);
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('start_at', '>=', $start)
            ->where('start_at', '<=', $end)
            ->with('provider:id,name')
            ->get()
            ->map(fn ($b) => [
                'id'       => 'bk-' . $b->id,
                'type'     => 'booking',
                'title'    => $b->title,
                'date'     => Carbon::parse($b->start_at)->toDateString(),
                'time'     => Carbon::parse($b->start_at)->format('H:i'),
                'time_end' => Carbon::parse($b->end_at)->format('H:i'),
                'color'    => $b->status === 'confirmed' ? '#a78bfa' : '#fbbf24',
                'meta'     => 'with ' . $b->provider->name,
                'overdue'  => false,
                'model_id' => $b->id,
            ]);

        // Merge and group by date
        $all = $tasks->concat($milestones)->concat($bookings)
            ->sortBy('time')
            ->groupBy('date')
            ->toArray();

        return $all;
    }

    #[Computed]
    public function weekDays(): array
    {
        $anchor = Carbon::parse($this->currentDate);
        $start  = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $days   = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'date'    => $d->toDateString(),
                'label'   => $d->format('D'),
                'day'     => $d->format('j'),
                'isToday' => $d->isToday(),
            ];
        }
        return $days;
    }

    #[Computed]
    public function monthGrid(): array
    {
        $anchor    = Carbon::parse($this->currentDate);
        $gridStart = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd   = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks     = [];
        $cursor    = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $d = $cursor->copy();
                $week[] = [
                    'date'         => $d->toDateString(),
                    'day'          => $d->format('j'),
                    'isToday'      => $d->isToday(),
                    'isCurrentMonth' => $d->month === $anchor->month,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    #[Computed]
    public function upcoming(): array
    {
        $user = Auth::user();

        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', today())
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type'  => 'task',
                'title' => $t->title,
                'date'  => $t->due_date->format('M d'),
                'meta'  => $t->project->name ?? 'Personal',
                'color' => '#60a5fa',
                'id'    => $t->id,
            ]);

        $bookings = Booking::where(function ($q) use ($user) {
                $q->where('provider_id', $user->id)->orWhere('client_id', $user->id);
            })
            ->where('status', 'confirmed')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(3)
            ->get()
            ->map(fn ($b) => [
                'type'  => 'booking',
                'title' => $b->title,
                'date'  => Carbon::parse($b->start_at)->format('M d, H:i'),
                'meta'  => 'Meeting',
                'color' => '#a78bfa',
                'id'    => $b->id,
            ]);

        return $tasks->concat($bookings)->sortBy('date')->values()->toArray();
    }

    #[Computed]
    public function heading(): string
    {
        $anchor = Carbon::parse($this->currentDate);
        return match ($this->view) {
            'month' => $anchor->format('F Y'),
            'day'   => $anchor->format('l, F j Y'),
            default => $anchor->startOfWeek(Carbon::MONDAY)->format('M j') .
                       ' – ' .
                       $anchor->endOfWeek(Carbon::SUNDAY)->format('M j, Y'),
        };
    }

    public function render()
    {
        return view('livewire.backend.personalCalendar');
    }
}
