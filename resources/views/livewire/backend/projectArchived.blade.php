<x-slot name="header">Archive</x-slot>

<div class="max-w-5xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Project Archive</flux:heading>
            <flux:text class="mt-0.5">
                {{ $this->totalArchived }} archived project{{ $this->totalArchived !== 1 ? 's' : '' }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="folder-open" href="{{ route('backend.projectList') }}" wire:navigate>
            Active projects
        </flux:button>
    </div>

    {{-- Search --}}
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search archived projects…" icon="magnifying-glass"
        clearable class="max-w-sm" />

    {{-- Empty state --}}
    @if ($this->projects->isEmpty())
        <div
            class="flex flex-col items-center gap-3 py-16 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-[#131d2e]">
                <flux:icon.archive-box class="size-7 text-[#506070]" />
            </div>
            <div>
                <flux:heading>No archived projects</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ $search ? 'No matches for your search.' : 'Archived projects will appear here.' }}
                </flux:text>
            </div>
        </div>
    @else
        {{-- Archive table --}}
        <flux:table :paginate="$this->projects">
            <flux:table.columns sticky>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$direction"
                    wire:click="sort('name')">Project</flux:table.column>

                <flux:table.column>Final status</flux:table.column>
                <flux:table.column>Progress</flux:table.column>

                <flux:table.column sortable :sorted="$sortBy === 'archived_at'" :direction="$direction"
                    wire:click="sort('archived_at')">Archived</flux:table.column>

                <flux:table.column>Duration</flux:table.column>
                <flux:table.column>Team</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->projects as $project)
                    <flux:table.row :key="$project->id" wire:key="arch-{{ $project->id }}">

                        {{-- Name --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-xs font-bold font-['Syne']
                                    bg-[#131d2e] text-[#506070] border border-[#1c2e45]">
                                    {{ strtoupper(substr($project->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-[#8da0b8] line-clamp-1">{{ $project->name }}</p>
                                    @if ($project->client_name)
                                        <p class="text-[10px] text-[#506070] flex items-center gap-1">
                                            <flux:icon.building-office class="size-3" />
                                            {{ $project->client_name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Status --}}
                        <flux:table.cell>
                            <flux:badge size="sm"
                                :color="match($project->status) {
                                                                    'completed' => 'lime',
                                                                    'cancelled' => 'red',
                                                                    default     => 'zinc',
                                                                }">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}</flux:badge>
                        </flux:table.cell>

                        {{-- Progress --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-2 min-w-20">
                                <div class="flex-1 h-1 bg-[#1c2e45] rounded-full overflow-hidden">
                                    <div class="h-full bg-[#506070] rounded-full"
                                        style="width: {{ $project->progress_percentage }}%"></div>
                                </div>
                                <span class="text-[11px] font-mono text-[#506070] tabular-nums">
                                    {{ $project->progress_percentage }}%
                                </span>
                            </div>
                        </flux:table.cell>

                        {{-- Archived date --}}
                        <flux:table.cell>
                            <span class="text-xs text-[#8da0b8]">
                                {{ $project->archived_at->format('M d, Y') }}
                            </span>
                        </flux:table.cell>

                        {{-- Duration (start to archive) --}}
                        <flux:table.cell>
                            @if ($project->start_date)
                                <span class="text-xs text-[#506070]">
                                    {{ $project->start_date->diffForHumans($project->archived_at, true) }}
                                </span>
                            @else
                                <span class="text-xs text-[#506070]">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Team size --}}
                        <flux:table.cell>
                            <div class="flex items-center gap-1 text-xs text-[#506070]">
                                <flux:icon.user-group class="size-3.5" />
                                {{ $project->projectMembers()->count() }}
                            </div>
                        </flux:table.cell>

                        {{-- Actions --}}
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                    inset="top bottom" />
                                <flux:menu>
                                    {{-- View read-only --}}
                                    <flux:menu.item icon="eye" href="{{ route('backend.projectList', $project) }}"
                                        wire:navigate>
                                        View project
                                    </flux:menu.item>

                                    {{-- Portfolio prompt (marketplace users only) --}}
                                    @if (Auth::user()->is_marketplace_enabled)
                                        <flux:menu.item icon="star"
                                            href="{{ route('backend.editProfile', ['tab' => 'portfolio', 'from_project' => $project->id]) }}"
                                            Add to portfolio </flux:menu.item>
                                    @endif

                                    <flux:menu.separator />

                                    {{-- Unarchive --}}
                                    <flux:menu.item icon="arrow-path" wire:click="unarchive({{ $project->id }})">
                                        Restore to active</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

    @endif

</div>
