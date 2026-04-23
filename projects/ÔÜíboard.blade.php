<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectActivity;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component
{
    public Project $project;
    public string  $view         = 'kanban';   // kanban | list
    public ?int    $openTaskId   = null;        // drawer open
    public string  $actSearch    = '';

    // New task quick-add
    public string  $newTaskTitle  = '';
    public string  $newTaskColumn = 'todo';
    public bool    $showQuickAdd  = false;

    protected function getListeners(): array
    {
        return [
            "echo-private:project.{$this->project->id},.task.updated"     => 'handleTaskUpdated',
            "echo-private:project.{$this->project->id},.progress.updated" => 'handleProgressUpdated',
            "echo-private:project.{$this->project->id},.activity.new"     => '$refresh',
        ];
    }

    public function mount(Project $project): void
    {
        // Authorization: must be project member
        abort_unless(
            $project->projectMembers()->where('user_id', Auth::id())->exists(),
            403
        );
        $this->project = $project;
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
        $isManager  = $this->project->projectMembers()
                           ->where('user_id', Auth::id())
                           ->whereIn('role', ['manager','owner'])
                           ->exists();
        abort_unless($isAssignee || $isManager, 403);

        $task->transitionTo($newStatus);   // ALG-TASK-02 in Task model
        $this->project->recalculateProgress();

        $this->dispatch('toast', ['message' => "Task moved to ".ucfirst(str_replace('_',' ',$newStatus)), 'type' => 'success']);
    }

    // ── Quick-add task ───────────────────────────────────────
    public function quickAdd(): void
    {
        $this->validate(['newTaskTitle' => ['required','string','max:255']]);

        Task::create([
            'project_id' => $this->project->id,
            'title'      => $this->newTaskTitle,
            'status'     => $this->newTaskColumn,
            'priority'   => 'medium',
            'sort_order' => Task::where('project_id', $this->project->id)->max('sort_order') + 1,
        ]);

        $this->project->recalculateProgress();
        $this->newTaskTitle = '';
        $this->showQuickAdd = false;
    }

    public function openTask(int $id): void  { $this->openTaskId = $id; }
    public function closeTask(): void        { $this->openTaskId = null; }

    #[Computed]
    public function columns(): array
    {
        return [
            'todo'        => ['label' => 'To Do',      'color' => 'zinc',   'icon' => 'queue-list'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'blue',   'icon' => 'arrow-path'],
            'in_review'   => ['label' => 'In Review',   'color' => 'yellow', 'icon' => 'eye'],
            'done'        => ['label' => 'Done',        'color' => 'green',  'icon' => 'check-circle'],
            'blocked'     => ['label' => 'Blocked',     'color' => 'red',    'icon' => 'no-symbol'],
        ];
    }

    #[Computed]
    public function tasksByStatus(): array
    {
        $tasks = Task::where('project_id', $this->project->id)
                     ->whereNull('parent_task_id')
                     ->with(['assignee','milestone'])
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
                   ->with(['assignee','milestone'])
                   ->orderBy('status')->orderBy('sort_order')
                   ->get();
    }

    #[Computed]
    public function activityFeed()
    {
        return \App\Models\ProjectActivity::where('project_id', $this->project->id)
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
}; ?>

<x-slot name="header">{{ $project->name }}</x-slot>

<div class="flex flex-col gap-4 max-w-full" x-data="{ taskOpen: false, taskId: null }">

    {{-- ── Project top bar ─────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl px-4 py-3">

        {{-- Project meta --}}
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-['Syne'] text-sm font-bold bg-[#7EE8A2]/10 text-[#7EE8A2] border border-[#7EE8A2]/15">
                {{ strtoupper(substr($project->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <h1 class="font-['Syne'] font-bold text-white text-base truncate">{{ $project->name }}</h1>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <flux:badge
                        size="sm"
                        :color="match($project->status) {
                            'active'    => 'green',
                            'planning'  => 'blue',
                            'on_hold'   => 'yellow',
                            'completed' => 'lime',
                            default     => 'zinc'
                        }"
                    >{{ ucfirst(str_replace('_',' ',$project->status)) }}</flux:badge>

                    @if($project->github_repo)
                        <span class="flex items-center gap-1 text-[11px] text-[#506070]">
                            <flux:icon.code-bracket class="size-3"/>
                            {{ $project->github_repo }}
                        </span>
                    @endif

                    @if($project->due_date)
                        <span class="flex items-center gap-1 text-[11px] {{ $project->isOverdue() ? 'text-red-400' : 'text-[#506070]' }}">
                            <flux:icon.calendar class="size-3"/>
                            Due {{ $project->due_date->format('M d, Y') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="flex items-center gap-2.5 w-48">
            <div class="flex-1 h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                <div
                    class="h-full bg-[#7EE8A2] rounded-full transition-all duration-700"
                    style="width: {{ $project->progress_percentage }}%"
                ></div>
            </div>
            <span class="text-xs font-mono text-[#506070] tabular-nums w-8">{{ $project->progress_percentage }}%</span>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            {{-- View toggle --}}
            <flux:button.group>
                <flux:button
                    variant="{{ $view === 'kanban' ? 'filled' : 'ghost' }}"
                    size="sm" icon="view-columns"
                    wire:click="$set('view','kanban')"
                    title="Kanban"
                />
                <flux:button
                    variant="{{ $view === 'list' ? 'filled' : 'ghost' }}"
                    size="sm" icon="list-bullet"
                    wire:click="$set('view','list')"
                    title="List"
                />
            </flux:button.group>

            <flux:button
                variant="primary" size="sm" icon="plus"
                wire:click="$set('showQuickAdd',true)"
            >Add task</flux:button>

            <flux:button
                variant="ghost" size="sm" icon="cog-6-tooth"
                href="{{ route('projects.settings', $project) }}" wire:navigate
            />
        </div>
    </div>

    {{-- ── Main layout: board + sidebar ───────────────────── --}}
    <div class="flex gap-4 items-start">

        {{-- ── Board area ──────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- ── KANBAN VIEW ─────────────────────────────── --}}
            @if($view === 'kanban')
                <div class="flex gap-3 overflow-x-auto pb-2" style="min-height:60vh">
                    @foreach($this->columns as $status => $col)
                        <div class="flex flex-col w-72 flex-shrink-0">

                            {{-- Column header --}}
                            <div class="flex items-center justify-between mb-2.5 px-1">
                                <div class="flex items-center gap-2">
                                    <flux:icon :name="$col['icon']" class="size-3.5 text-[#506070]"/>
                                    <span class="text-xs font-semibold text-[#8da0b8]">{{ $col['label'] }}</span>
                                    <flux:badge size="sm" :color="$col['color']" class="font-mono">
                                        {{ $this->tasksByStatus[$status]->count() }}
                                    </flux:badge>
                                </div>
                                <flux:button
                                    variant="ghost" size="sm" icon="plus" class="!p-1"
                                    wire:click="$set('newTaskColumn','{{ $status }}'); $set('showQuickAdd',true)"
                                />
                            </div>

                            {{-- Task cards --}}
                            <div class="flex flex-col gap-2 flex-1">
                                @forelse($this->tasksByStatus[$status] as $task)
                                    <div
                                        wire:key="task-{{ $task->id }}"
                                        wire:click="openTask({{ $task->id }})"
                                        class="group bg-[#0e1420] border border-[#1c2e45] rounded-xl p-3.5 cursor-pointer hover:border-[#254060] hover:-translate-y-0.5 transition-all duration-200"
                                    >
                                        {{-- Task top row --}}
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <p class="text-sm text-[#dde6f0] leading-snug line-clamp-2 flex-1">
                                                {{ $task->title }}
                                            </p>
                                            <div class="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1
                                                {{ match($task->priority) {
                                                    'critical' => 'bg-red-500',
                                                    'high'     => 'bg-orange-400',
                                                    'medium'   => 'bg-amber-400',
                                                    default    => 'bg-blue-400',
                                                } }}">
                                            </div>
                                        </div>

                                        {{-- Milestone --}}
                                        @if($task->milestone)
                                            <div class="flex items-center gap-1 mb-2">
                                                <flux:icon.flag class="size-3 text-[#506070]"/>
                                                <span class="text-[10px] text-[#506070] truncate">{{ $task->milestone->name }}</span>
                                            </div>
                                        @endif

                                        {{-- Task footer --}}
                                        <div class="flex items-center justify-between mt-1">
                                            <div class="flex items-center gap-2">
                                                @if($task->due_date)
                                                    <span class="text-[10px] {{ $task->isOverdue() ? 'text-red-400' : 'text-[#506070]' }} flex items-center gap-0.5">
                                                        <flux:icon.calendar class="size-3"/>
                                                        {{ $task->due_date->format('M d') }}
                                                    </span>
                                                @endif
                                                @if($task->deliverable_url)
                                                    <flux:icon.paper-clip class="size-3 text-[#506070]"/>
                                                @endif
                                            </div>

                                            @if($task->assignee)
                                                <flux:avatar
                                                    src="{{ $task->assignee->avatar_url }}"
                                                    name="{{ $task->assignee->name }}"
                                                    size="xs"
                                                    class="ring-1 ring-[#080c14]"
                                                />
                                            @endif
                                        </div>

                                        {{-- Quick status move --}}
                                        <div class="hidden group-hover:flex gap-1 mt-2.5 pt-2 border-t border-[#1c2e45]" wire:click.stop>
                                            @foreach(array_keys($this->columns) as $s)
                                                @if($s !== $status)
                                                    <button
                                                        wire:click="moveTask({{ $task->id }},'{{ $s }}')"
                                                        class="flex-1 text-[10px] text-[#506070] hover:text-[#7EE8A2] py-0.5 rounded transition-colors truncate"
                                                        title="Move to {{ $this->columns[$s]['label'] }}"
                                                    >{{ $this->columns[$s]['label'] }}</button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center gap-1 py-6 border border-dashed border-[#1c2e45] rounded-xl text-center">
                                        <flux:icon.inbox class="size-6 text-[#506070]"/>
                                        <span class="text-xs text-[#506070]">No tasks</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── LIST VIEW ────────────────────────────────── --}}
            @if($view === 'list')
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Task</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Priority</flux:table.column>
                        <flux:table.column>Assignee</flux:table.column>
                        <flux:table.column>Due date</flux:table.column>
                        <flux:table.column>Milestone</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->allTasks as $task)
                            <flux:table.row :key="$task->id" wire:key="lt-{{ $task->id }}">

                                <flux:table.cell>
                                    <button wire:click="openTask({{ $task->id }})" class="text-left group">
                                        <p class="text-sm text-[#dde6f0] group-hover:text-[#7EE8A2] transition-colors line-clamp-1">
                                            {{ $task->title }}
                                        </p>
                                    </button>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:select
                                        wire:change="moveTask({{ $task->id }}, $event.target.value)"
                                        size="sm"
                                        class="text-xs"
                                    >
                                        @foreach($this->columns as $s => $col)
                                            <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>
                                                {{ $col['label'] }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full
                                            {{ match($task->priority) {
                                                'critical' => 'bg-red-500',
                                                'high'     => 'bg-orange-400',
                                                'medium'   => 'bg-amber-400',
                                                default    => 'bg-blue-400',
                                            } }}">
                                        </div>
                                        <span class="text-xs text-[#8da0b8] capitalize">{{ $task->priority }}</span>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($task->assignee)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar src="{{ $task->assignee->avatar_url }}" name="{{ $task->assignee->name }}" size="xs"/>
                                            <span class="text-xs text-[#8da0b8] truncate max-w-[80px]">{{ $task->assignee->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-[#506070]">Unassigned</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($task->due_date)
                                        <span class="text-xs {{ $task->isOverdue() ? 'text-red-400' : 'text-[#8da0b8]' }}">
                                            {{ $task->due_date->format('M d') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[#506070]">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($task->milestone)
                                        <flux:badge size="sm" color="zinc">{{ $task->milestone->name }}</flux:badge>
                                    @else
                                        <span class="text-xs text-[#506070]">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:button
                                        variant="ghost" size="sm" icon="arrow-top-right-on-square"
                                        wire:click="openTask({{ $task->id }})"
                                    />
                                </flux:table.cell>

                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-[#506070]">
                                        <flux:icon.inbox class="size-8"/>
                                        <span class="text-sm">No tasks yet</span>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            @endif

        </div>

        {{-- ── Activity + team sidebar ─────────────────────── --}}
        <div class="w-72 flex-shrink-0 space-y-4 hidden xl:block">

            {{-- Team --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-4">
                <div class="flex items-center justify-between mb-3">
                    <flux:heading size="sm">Team</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="user-plus"
                        href="{{ route('projects.settings', $project) }}" wire:navigate/>
                </div>
                <div class="space-y-2">
                    @foreach($this->members->take(6) as $m)
                        <div class="flex items-center gap-2.5">
                            <flux:avatar src="{{ $m->user->avatar_url }}" name="{{ $m->user->name }}" size="xs"/>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-[#dde6f0] truncate">{{ $m->user->name }}</p>
                                <p class="text-[10px] text-[#506070]">{{ ucfirst(str_replace('_',' ',$m->role)) }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if($this->members->count() > 6)
                        <p class="text-xs text-[#506070] text-center">+{{ $this->members->count() - 6 }} more</p>
                    @endif
                </div>
            </flux:card>

            {{-- Activity feed --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-4">
                <flux:heading size="sm" class="mb-3">Activity</flux:heading>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($this->activityFeed as $act)
                        <div class="flex gap-2.5" wire:key="act-{{ $act->id }}">
                            <div class="w-1.5 h-1.5 rounded-full bg-[#254060] flex-shrink-0 mt-1.5"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-[#8da0b8] leading-snug">
                                    @if($act->github_url)
                                        <a href="{{ $act->github_url }}" target="_blank" class="text-[#7EE8A2] hover:underline">
                                            {{ $act->actor ?? $act->user?->name ?? 'System' }}
                                        </a>
                                    @else
                                        <span class="font-medium text-[#dde6f0]">{{ $act->user?->name ?? 'System' }}</span>
                                    @endif
                                    {{ $act->description }}
                                </p>
                                <p class="text-[10px] text-[#506070] mt-0.5">{{ $act->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[#506070] text-center py-4">No activity yet</p>
                    @endforelse
                </div>
            </flux:card>

        </div>

    </div>

</div>

{{-- ── Quick Add Task Modal ──────────────────────────── --}}
<flux:modal wire:model="showQuickAdd" class="max-w-md">
    <div class="space-y-4">
        <flux:heading>Add task</flux:heading>

        <form wire:submit="quickAdd" class="space-y-4">
            <flux:field>
                <flux:label>Task title <flux:required/></flux:label>
                <flux:input wire:model="newTaskTitle" placeholder="Describe the task…" autofocus/>
                <flux:error name="newTaskTitle"/>
            </flux:field>

            <flux:field>
                <flux:label>Add to column</flux:label>
                <flux:select wire:model="newTaskColumn">
                    @foreach($this->columns as $s => $col)
                        <flux:option value="{{ $s }}">{{ $col['label'] }}</flux:option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Add task</span>
                    <span wire:loading>Adding…</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>

{{-- ── Task Detail Drawer (Phase 4) ─────────────────── --}}
@if($openTaskId)
    <div
        class="fixed inset-0 z-40 flex justify-end"
        wire:key="task-drawer-{{ $openTaskId }}"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
            wire:click="closeTask"
        ></div>

        {{-- Drawer panel --}}
        <div class="relative z-50 w-full max-w-xl bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl animate-slide-in-right">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <flux:heading>Task Detail</flux:heading>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeTask"/>
            </div>
            <div class="p-5">
                @livewire('tasks.task-detail', ['taskId' => $openTaskId], key('drawer-'.$openTaskId))
            </div>
        </div>
    </div>
@endif

<style>
@keyframes slide-in-right {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
.animate-slide-in-right { animation: slide-in-right .25s ease both; }
</style>
