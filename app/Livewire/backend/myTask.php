<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Task;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
#[Title('My Tasks')]
class MyTask extends Component
{
    public string $filterProject = 'all';
    public string $filterPriority = 'all';
    public string $search = '';
    public ?int $openTaskId = null;

    protected $listeners = [];

    public function mount(): void
    {
        // Set up Echo listener for task assignments
        $this->listeners = [
            'echo-private:user.' . Auth::id() . ',.task.assigned' => '$refresh',
        ];
    }

    public function markDone(int $taskId): void
    {
        $task = Task::where('id', $taskId)
            ->where('assigned_to', Auth::id())
            ->firstOrFail();

        $task->transitionTo('done');
        $task->project->recalculateProgress();

        $this->dispatch('toast', ['message' => 'Task completed!', 'type' => 'success']);
    }

    public function openTask(int $id): void
    {
        $this->openTaskId = $id;
    }

    public function closeTask(): void
    {
        $this->openTaskId = null;
    }

    #[Computed]
    public function grouped(): array
    {
        $query = Task::where('assigned_to', Auth::id())
            ->whereNotIn('status', ['done'])
            ->with('project')
            ->when($this->filterPriority !== 'all', fn($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterProject !== 'all', fn($q) => $q->where('project_id', $this->filterProject))
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'));

        $tasks = $query->orderBy('due_date')->get();

        return [
            'overdue' => [
                'label' => 'Overdue',
                'color' => 'red',
                'tasks' => $tasks->filter(fn($t) => $t->due_date && $t->due_date->isPast()),
            ],
            'today' => [
                'label' => 'Due Today',
                'color' => 'yellow',
                'tasks' => $tasks->filter(fn($t) => $t->due_date && $t->due_date->isToday()),
            ],
            'week' => [
                'label' => 'Due This Week',
                'color' => 'blue',
                'tasks' => $tasks->filter(fn($t) =>
                    $t->due_date
                    && !$t->due_date->isPast()
                    && !$t->due_date->isToday()
                    && $t->due_date->lte(now()->endOfWeek())
                ),
            ],
            'upcoming' => [
                'label' => 'Upcoming',
                'color' => 'zinc',
                'tasks' => $tasks->filter(fn($t) =>
                    $t->due_date && $t->due_date->gt(now()->endOfWeek())
                ),
            ],
            'no_date' => [
                'label' => 'No Due Date',
                'color' => 'zinc',
                'tasks' => $tasks->filter(fn($t) => !$t->due_date),
            ],
        ];
    }

    #[Computed]
    public function stats(): array
    {
        $base = Task::where('assigned_to', Auth::id());
        return [
            'total' => (clone $base)->whereNotIn('status', ['done'])->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['done'])->whereDate('due_date', '<', today())->count(),
            'today' => (clone $base)->whereNotIn('status', ['done'])->whereDate('due_date', today())->count(),
            'done_week' => (clone $base)->where('status', 'done')->whereDate('updated_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    #[Computed]
    public function userProjects()
    {
        return ProjectMember::where('user_id', Auth::id())
            ->with('project:id,name')
            ->get()
            ->pluck('project')
            ->unique('id');
    }

    public function render()
    {
        return view('livewire.backend.myTask', [
            'grouped' => $this->grouped,
            'stats' => $this->stats,
            'userProjects' => $this->userProjects,
        ]);
    }
}