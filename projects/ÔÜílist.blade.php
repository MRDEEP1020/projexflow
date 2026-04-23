<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

new #[Layout('layouts.app')] #[Title('Projects')] class extends Component
{
    use WithPagination;

    public string $search    = '';
    public string $status    = 'all';
    public string $sortBy    = 'updated_at';
    public string $direction = 'desc';

    protected function getListeners(): array
    {
        $orgId = Session::get('active_org_id');
        return $orgId ? [
            "echo-private:org.{$orgId},.project.created" => '$refresh',
        ] : [];
    }

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingStatus(): void  { $this->resetPage(); }

    public function sort(string $col): void
    {
        if ($this->sortBy === $col) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy    = $col;
            $this->direction = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function projects()
    {
        $orgId = Session::get('active_org_id');
        $user  = Auth::user();

        return Project::query()
            ->when($orgId,  fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNull('archived_at')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->orderBy($this->sortBy, $this->direction)
            ->paginate(12);
    }

    #[Computed]
    public function counts(): array
    {
        $orgId = Session::get('active_org_id');
        $user  = Auth::user();

        $base = Project::query()
            ->when($orgId,  fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNull('archived_at');

        return [
            'all'       => (clone $base)->count(),
            'planning'  => (clone $base)->where('status','planning')->count(),
            'active'    => (clone $base)->where('status','active')->count(),
            'on_hold'   => (clone $base)->where('status','on_hold')->count(),
            'completed' => (clone $base)->where('status','completed')->count(),
        ];
    }
}; ?>

<x-slot name="header">Projects</x-slot>

<div class="space-y-5 max-w-7xl">

    {{-- ── Header row ───────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Projects</flux:heading>
            <flux:text class="mt-0.5">{{ $this->counts['all'] }} total across your workspace</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" href="{{ route('projects.create') }}" wire:navigate>
            New project
        </flux:button>
    </div>

    {{-- ── Filters ──────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3">
        {{-- Search --}}
        <div class="flex-1 min-w-48 max-w-xs">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search projects…"
                icon="magnifying-glass"
                clearable
            />
        </div>

        {{-- Status tabs --}}
        <flux:tabs wire:model.live="status" variant="segmented" size="sm">
            <flux:tab name="all">
                All
                <flux:badge size="sm" color="zinc" class="ml-1">{{ $this->counts['all'] }}</flux:badge>
            </flux:tab>
            <flux:tab name="active">
                Active
                <flux:badge size="sm" color="green" class="ml-1">{{ $this->counts['active'] }}</flux:badge>
            </flux:tab>
            <flux:tab name="planning">Planning</flux:tab>
            <flux:tab name="on_hold">On Hold</flux:tab>
            <flux:tab name="completed">Completed</flux:tab>
        </flux:tabs>
    </div>

    {{-- ── Project table ────────────────────────────────── --}}
    @if($this->projects->isEmpty())
        <div class="flex flex-col items-center gap-3 py-16 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-[#131d2e]">
                <flux:icon.folder-open class="size-7 text-[#506070]"/>
            </div>
            <div>
                <flux:heading>No projects found</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ $search ? 'Try a different search term.' : 'Create your first project to get started.' }}
                </flux:text>
            </div>
            @if(!$search)
                <flux:button variant="primary" size="sm" href="{{ route('projects.create') }}" wire:navigate>
                    Create project
                </flux:button>
            @endif
        </div>
    @else
        <flux:table :paginate="$this->projects" pagination:scroll-to="body">

            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$direction"
                    wire:click="sort('name')"
                >Project</flux:table.column>

                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Priority</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'progress_percentage'"
                    :direction="$direction"
                    wire:click="sort('progress_percentage')"
                >Progress</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'due_date'"
                    :direction="$direction"
                    wire:click="sort('due_date')"
                >Due date</flux:table.column>

                <flux:table.column>Team</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->projects as $project)
                    <flux:table.row :key="$project->id" wire:key="project-{{ $project->id }}">

                        {{-- Project name + github badge --}}
                        <flux:table.cell>
                            <a href="{{ route('projects.show', $project) }}" wire:navigate
                               class="flex items-center gap-2.5 group">
                                <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold font-['Syne']
                                    bg-[#7EE8A2]/10 text-[#7EE8A2] border border-[#7EE8A2]/15">
                                    {{ strtoupper(substr($project->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#dde6f0] group-hover:text-[#7EE8A2] transition-colors line-clamp-1">
                                        {{ $project->name }}
                                    </p>
                                    @if($project->github_repo)
                                        <p class="text-[10px] text-[#506070] flex items-center gap-1 mt-0.5">
                                            <flux:icon.code-bracket class="size-3"/>
                                            {{ $project->github_repo }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($project->status) {
                                    'active'    => 'green',
                                    'planning'  => 'blue',
                                    'on_hold'   => 'yellow',
                                    'completed' => 'lime',
                                    'cancelled' => 'red',
                                    default     => 'zinc',
                                }"
                            >{{ ucfirst(str_replace('_',' ',$project->status)) }}</flux:badge>
                        </flux:table.cell>

                        {{-- Priority --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full
                                    {{ match($project->priority) {
                                        'critical' => 'bg-red-500',
                                        'high'     => 'bg-orange-400',
                                        'medium'   => 'bg-amber-400',
                                        default    => 'bg-blue-400',
                                    } }}">
                                </div>
                                <span class="text-xs text-[#8da0b8] capitalize">{{ $project->priority }}</span>
                            </div>
                        </flux:table.cell>

                        {{-- Progress --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2 min-w-24">
                                <div class="flex-1 h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                                    <div class="h-full bg-[#7EE8A2] rounded-full transition-all"
                                         style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono text-[#506070] w-8 text-right tabular-nums">
                                    {{ $project->progress_percentage }}%
                                </span>
                            </div>
                        </flux:table.cell>

                        {{-- Due date --}}
                        <flux:table.cell>
                            @if($project->due_date)
                                <span class="text-xs {{ $project->isOverdue() ? 'text-red-400' : 'text-[#8da0b8]' }}">
                                    {{ $project->due_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-xs text-[#506070]">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Team member count --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-1 text-xs text-[#8da0b8]">
                                <flux:icon.user-group class="size-3.5"/>
                                {{ $project->projectMembers()->count() }}
                            </div>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"/>
                                <flux:menu>
                                    <flux:menu.item icon="arrow-top-right-on-square"
                                        href="{{ route('projects.show', $project) }}" wire:navigate>
                                        Open board
                                    </flux:menu.item>
                                    <flux:menu.item icon="cog-6-tooth"
                                        href="{{ route('projects.settings', $project) }}" wire:navigate>
                                        Settings
                                    </flux:menu.item>
                                    <flux:menu.item icon="user-plus"
                                        href="{{ route('projects.settings', $project) }}" wire:navigate>
                                        Add members
                                    </flux:menu.item>
                                    <flux:menu.separator/>
                                    <flux:menu.item icon="archive-box" variant="danger"
                                        href="{{ route('archive') }}" wire:navigate>
                                        Archive
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @endforeach
            </flux:table.rows>

        </flux:table>
    @endif

</div>
