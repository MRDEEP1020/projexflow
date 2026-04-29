<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectActivity;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
class ProjectBoard extends Component
{
    public Project $project;
    public string $view = 'kanban';   // kanban | list
    public ?int $openTaskId = null;   // drawer open
    public string $actSearch = '';
    
    // New task quick-add
    public string $newTaskTitle = '';
    public string $newTaskColumn = 'todo';
    public bool $showQuickAdd = false;
    
    protected $listeners = [
        'task-updated' => 'handleTaskUpdated',
        'progress-updated' => 'handleProgressUpdated',
        'activity-new' => '$refresh',
    ];
    
    public function mount(Project $project): void
    {
        // Authorization: must be project member
        abort_unless(
            $project->projectMembers()->where('user_id', Auth::id())->exists(),
            403
        );
        $this->project = $project;
        
        // Set up Echo listeners dynamically
        $this->listeners = array_merge($this->listeners, [
            "echo-private:project.{$this->project->id},.task.updated" => 'handleTaskUpdated',
            "echo-private:project.{$this->project->id},.progress.updated" => 'handleProgressUpdated',
            "echo-private:project.{$this->project->id},.activity.new" => '$refresh',
        ]);
    }
    
    public function handleTaskUpdated(array $data): void
    {
        // Refresh task in the current collection without full re-render
        $this->project->refresh();
    }
    
    public function handleProgressUpdated(array $data): void
    {
        $this->project->refresh();
    }
    
    // ── Task status change (Kanban drag or button) ──────────
    public function moveTask(int $taskId, string $newStatus): void
    {
        $task = Task::where('id', $taskId)
                    ->where('project_id', $this->project->id)
                    ->firstOrFail();
        
        // Authorization: assignee or manager
        $isAssignee = $task->assigned_to === Auth::id();
        $isManager = $this->project->projectMembers()
                           ->where('user_id', Auth::id())
                           ->whereIn('role', ['manager', 'owner'])
                           ->exists();
        abort_unless($isAssignee || $isManager, 403);
        
        $task->transitionTo($newStatus);   // ALG-TASK-02 in Task model
        $this->project->recalculateProgress();
        
        $this->dispatch('toast', ['message' => "Task moved to " . ucfirst(str_replace('_', ' ', $newStatus)), 'type' => 'success']);
    }
    
    // ── Quick-add task ───────────────────────────────────────
    public function quickAdd(): void
    {
        $this->validate(['newTaskTitle' => ['required', 'string', 'max:255']]);
        
        Task::create([
            'project_id' => $this->project->id,
            'title' => $this->newTaskTitle,
            'status' => $this->newTaskColumn,
            'priority' => 'medium',
            'sort_order' => Task::where('project_id', $this->project->id)->max('sort_order') + 1,
            'created_by' => Auth::id(),
        ]);
        
        $this->project->recalculateProgress();
        $this->newTaskTitle = '';
        $this->showQuickAdd = false;
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
    public function columns(): array
    {
        return [
            'todo' => ['label' => 'To Do', 'color' => 'zinc', 'icon' => 'queue-list'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'blue', 'icon' => 'arrow-path'],
            'in_review' => ['label' => 'In Review', 'color' => 'yellow', 'icon' => 'eye'],
            'done' => ['label' => 'Done', 'color' => 'green', 'icon' => 'check-circle'],
            'blocked' => ['label' => 'Blocked', 'color' => 'red', 'icon' => 'no-symbol'],
        ];
    }
    
    #[Computed]
    public function tasksByStatus(): array
    {
        $tasks = Task::where('project_id', $this->project->id)
                     ->whereNull('parent_task_id')
                     ->with(['assignee', 'milestone'])
                     ->orderBy('sort_order')
                     ->get()
                     ->groupBy('status');
        
        $result = [];
        foreach (array_keys($this->columns) as $status) {
            $result[$status] = $tasks->get($status, collect());
        }
        return $result;
    }
    
    #[Computed]
    public function allTasks()
    {
        return Task::where('project_id', $this->project->id)
                   ->whereNull('parent_task_id')
                   ->with(['assignee', 'milestone'])
                   ->orderBy('status')
                   ->orderBy('sort_order')
                   ->get();
    }
    
    #[Computed]
    public function activityFeed()
    {
        return ProjectActivity::where('project_id', $this->project->id)
                   ->with('user')
                   ->latest()
                   ->limit(25)
                   ->get();
    }
    
    #[Computed]
    public function members()
    {
        return $this->project->projectMembers()->with('user')->get();
    }
    
    public function render()
    {
        return view('livewire.backend.projectBoard', [
            'columns' => $this->columns,
            'tasksByStatus' => $this->tasksByStatus,
            'allTasks' => $this->allTasks,
            'activityFeed' => $this->activityFeed,
            'members' => $this->members,
        ]);
    }
}