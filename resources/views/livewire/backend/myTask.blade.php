<div>
    <x-slot name="header">My Tasks</x-slot>

    <div class="max-w-4xl space-y-6">

        {{-- ── Stats row ────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([
                ['label' => 'Total open',      'value' => $stats['total'],     'color' => 'text-white'],
                ['label' => 'Overdue',         'value' => $stats['overdue'],   'color' => 'text-red-400'],
                ['label' => 'Due today',       'value' => $stats['today'],     'color' => 'text-amber-400'],
                ['label' => 'Done this week',  'value' => $stats['done_week'], 'color' => 'text-[#7EE8A2]'],
            ] as $stat)
                <div class="flex items-center gap-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4">
                    <div>
                        <p class="font-['Syne'] text-2xl font-bold {{ $stat['color'] }} leading-none">{{ $stat['value'] }}</p>
                        <p class="text-xs text-[#506070] mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Filters ──────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search tasks…"
                icon="magnifying-glass"
                clearable
                class="flex-1 min-w-48 max-w-xs"
            />

            <flux:select wire:model.live="filterProject" class="w-44">
                <flux:select.option value="all">All projects</flux:select.option>
                @foreach($userProjects as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterPriority" class="w-36">
                <flux:select.option value="all">All priorities</flux:select.option>
                <flux:select.option value="critical">Critical</flux:select.option>
                <flux:select.option value="high">High</flux:select.option>
                <flux:select.option value="medium">Medium</flux:select.option>
                <flux:select.option value="low">Low</flux:select.option>
            </flux:select>
        </div>

        {{-- ── Task groups ──────────────────────────────────────── --}}
        @foreach($grouped as $key => $group)
            @if($group['tasks']->isNotEmpty())
                <div x-data="{ open: {{ $key === 'no_date' ? 'false' : 'true' }} }">

                    {{-- Group header --}}
                    <button
                        @click="open = !open"
                        class="w-full flex items-center gap-2.5 mb-2 group"
                    >
                        <span class="font-['Syne'] text-sm font-semibold text-[#dde6f0]">
                            {{ $group['label'] }}
                        </span>
                        <flux:badge size="sm" color="$group['color']">{{ $group['tasks']->count() }}</flux:badge>
                        <div class="flex-1 h-px bg-[#1c2e45] ml-1"></div>
                        <flux:icon.chevron-down
                            class="size-4 text-[#506070] transition-transform"
                            class="{ 'rotate-180': !open }"
                        />
                    </button>

                    {{-- Task list --}}
                    <div x-show="open" x-collapse class="space-y-1.5">
                        @foreach($group['tasks'] as $task)
                            <div
                                class="group flex items-center gap-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl px-4 py-3
                                    hover:border-[#254060] transition-all cursor-pointer"
                                wire:key="mt-{{ $task->id }}"
                                wire:click="openTask({{ $task->id }})"
                            >
                                {{-- Check button --}}
                                <button
                                    wire:click.stop="markDone({{ $task->id }})"
                                    class="w-5 h-5 flex-shrink-0 rounded-full border border-[#254060]
                                        hover:border-[#7EE8A2] hover:bg-[#7EE8A2]/10 transition-all flex items-center justify-center"
                                    title="Mark done"
                                >
                                    <flux:icon.check class="size-3 text-[#7EE8A2] opacity-0 group-hover:opacity-60 transition-opacity"/>
                                </button>

                                {{-- Task info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-[#dde6f0] truncate group-hover:text-white transition-colors">
                                        {{ $task->title }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-[#506070] flex items-center gap-1">
                                            <flux:icon.folder class="size-3"/>
                                            {{ $task->project->name ?? 'Personal' }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Priority badge --}}
                                <flux:badge
                                    size="sm"
                                    color="match($task->priority) {
                                        'critical' => 'red',
                                        'high'     => 'orange',
                                        'medium'   => 'yellow',
                                        default    => 'zinc',
                                    }"
                                >{{ ucfirst($task->priority) }}</flux:badge>

                                {{-- Status badge --}}
                                <flux:badge
                                    size="sm"
                                    color="match($task->status) {
                                        'in_progress' => 'blue',
                                        'in_review'   => 'yellow',
                                        'blocked'     => 'red',
                                        default       => 'zinc',
                                    }"
                                >{{ ucfirst(str_replace('_',' ',$task->status)) }}</flux:badge>

                                {{-- Due date --}}
                                @if($task->due_date)
                                    <span class="text-xs flex-shrink-0 {{ $task->isOverdue() ? 'text-red-400' : 'text-[#506070]' }}">
                                        {{ $task->due_date->format('M d') }}
                                    </span>
                                @endif

                                <flux:icon.arrow-right class="size-4 text-[#506070] opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"/>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        {{-- All clear --}}
        @if(collect($grouped)->every(fn($g) => $g['tasks']->isEmpty()))
            <div class="flex flex-col items-center gap-3 py-16 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-[#131d2e]">
                    <flux:icon.check-circle class="size-7 text-[#7EE8A2]"/>
                </div>
                <div>
                    <flux:heading>All clear!</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ $search || $filterPriority !== 'all' || $filterProject !== 'all'
                            ? 'No tasks match your filters.'
                            : 'No open tasks assigned to you.' }}
                    </flux:text>
                </div>
            </div>
        @endif

    </div>

    {{-- ── Task Detail Drawer ──────────────────────────────── --}}
    @if($openTaskId)
        <div class="fixed inset-0 z-40 flex justify-end" wire:key="my-task-drawer-{{ $openTaskId }}">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeTask"></div>
            <div class="relative z-50 w-full max-w-xl bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
                 style="animation: slideIn .25s ease both">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                    <flux:heading>Task Detail</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeTask"/>
                </div>
                <div class="p-5">
                    @livewire('backend.taskDetail', ['taskId' => $openTaskId], key('my-drawer-'.$openTaskId))
                </div>
            </div>
        </div>

        <style>
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to   { transform: translateX(0);    opacity: 1; }
            }
        </style>
    @endif
</div>