<div class="space-y-4 max-w-6xl">

    {{-- ── Top bar ──────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 justify-between">

        <div class="flex items-center gap-2">
            {{-- Prev / Today / Next --}}
            <flux:button variant="ghost" size="sm" icon="chevron-left" wire:click="prev"/>
            <flux:button variant="ghost" size="sm" wire:click="goToday">Today</flux:button>
            <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="next"/>

            <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff;min-width:200px">
                {{ $this->heading }}
            </h2>
        </div>

        <div class="flex items-center gap-2">
            {{-- View switcher --}}
            <flux:button.group>
                <flux:button
                    size="sm"
                    :variant="$view === 'month' ? 'filled' : 'ghost'"
                    wire:click="setView('month')"
                >Month</flux:button>
                <flux:button
                    size="sm"
                    :variant="$view === 'week' ? 'filled' : 'ghost'"
                    wire:click="setView('week')"
                >Week</flux:button>
                <flux:button
                    size="sm"
                    :variant="$view === 'day' ? 'filled' : 'ghost'"
                    wire:click="setView('day')"
                >Day</flux:button>
            </flux:button.group>

            <flux:button
                variant="primary" size="sm" icon="plus"
                href="{{ route('backend.availabilitySettings') }}" wire:navigate
            >Availability</flux:button>
        </div>
    </div>

    <div class="flex gap-4">

        {{-- ── Calendar grid ───────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- ── WEEK VIEW ────────────────────────────────────── --}}
            @if($view === 'week')
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl overflow-hidden">
                    {{-- Day headers --}}
                    <div class="grid grid-cols-7 border-b border-[#1c2e45]">
                        @foreach($this->weekDays as $day)
                            <div class="px-2 py-3 text-center border-r border-[#1c2e45] last:border-r-0">
                                <p class="text-[10px] font-mono uppercase text-[#506070]">{{ $day['label'] }}</p>
                                <div class="flex items-center justify-center mt-1">
                                    <span
                                        class="w-7 h-7 flex items-center justify-center rounded-full text-sm font-semibold"
                                        style="{{ $day['isToday']
                                            ? 'background:#7EE8A2;color:#080c14;font-family:Syne,sans-serif'
                                            : 'color:#dde6f0' }}"
                                    >{{ $day['day'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Events grid --}}
                    <div class="grid grid-cols-7 min-h-[400px]">
                        @foreach($this->weekDays as $day)
                            <div class="border-r border-[#1c2e45] last:border-r-0 p-1.5 space-y-1">
                                @foreach($this->events[$day['date']] ?? [] as $evt)
                                    <button
                                        @if($evt['type'] === 'task')
                                            wire:click="openTask({{ $evt['model_id'] }})"
                                        @endif
                                        class="w-full text-left px-2 py-1.5 rounded-lg text-xs transition-all hover:opacity-80"
                                        style="background:{{ $evt['color'] }}18;border-left:3px solid {{ $evt['color'] }};color:{{ $evt['color'] }}"
                                    >
                                        @if($evt['time'])
                                            <span class="font-mono text-[10px] block opacity-70">{{ $evt['time'] }}</span>
                                        @endif
                                        <span class="font-medium line-clamp-2 leading-snug">{{ $evt['title'] }}</span>
                                        <span class="text-[10px] opacity-60 block mt-0.5">{{ $evt['meta'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── MONTH VIEW ───────────────────────────────────── --}}
            @if($view === 'month')
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl overflow-hidden">
                    {{-- Day-of-week header --}}
                    <div class="grid grid-cols-7 border-b border-[#1c2e45]">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                            <div class="py-2.5 text-center">
                                <span class="text-[10px] font-mono uppercase text-[#506070]">{{ $d }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Month grid --}}
                    @foreach($this->monthGrid as $week)
                        <div class="grid grid-cols-7 border-b border-[#1c2e45] last:border-b-0">
                            @foreach($week as $cell)
                                <div
                                    class="min-h-[90px] border-r border-[#1c2e45] last:border-r-0 p-1.5"
                                    style="{{ ! $cell['isCurrentMonth'] ? 'opacity:.35' : '' }}"
                                >
                                    <div class="flex justify-end mb-1">
                                        <span
                                            class="w-6 h-6 flex items-center justify-center rounded-full text-xs"
                                            style="{{ $cell['isToday']
                                                ? 'background:#7EE8A2;color:#080c14;font-weight:700;font-family:Syne,sans-serif'
                                                : 'color:#8da0b8' }}"
                                        >{{ $cell['day'] }}</span>
                                    </div>

                                    @php $dayEvts = $this->events[$cell['date']] ?? []; @endphp
                                    @foreach(array_slice($dayEvts, 0, 3) as $evt)
                                        <button
                                            @if($evt['type'] === 'task')
                                                wire:click="openTask({{ $evt['model_id'] }})"
                                            @endif
                                            class="w-full text-left px-1.5 py-1 rounded text-[10px] mb-0.5 transition-all hover:opacity-80 truncate"
                                            style="background:{{ $evt['color'] }}18;color:{{ $evt['color'] }}"
                                        >{{ $evt['title'] }}</button>
                                    @endforeach

                                    @if(count($dayEvts) > 3)
                                        <p class="text-[10px] text-[#506070] px-1">+{{ count($dayEvts) - 3 }} more</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── DAY VIEW ─────────────────────────────────────── --}}
            @if($view === 'day')
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-[#1c2e45]">
                        <p style="font-family:'Syne',sans-serif;font-weight:600;font-size:14px;color:#fff">
                            {{ Carbon\Carbon::parse($currentDate)->format('l, F j Y') }}
                        </p>
                    </div>

                    @php $dayEvts = $this->events[$currentDate] ?? []; @endphp

                    @if(empty($dayEvts))
                        <div class="flex flex-col items-center gap-2 py-12 text-[#506070]">
                            <flux:icon.calendar class="size-8"/>
                            <p class="text-sm">No events scheduled for this day.</p>
                        </div>
                    @else
                        <div class="divide-y divide-[#1c2e45]">
                            @foreach($dayEvts as $evt)
                                <button
                                    @if($evt['type'] === 'task')
                                        wire:click="openTask({{ $evt['model_id'] }})"
                                    @endif
                                    class="w-full flex items-start gap-3 px-5 py-4 hover:bg-[#131d2e] text-left transition-colors"
                                >
                                    <div class="w-1 self-stretch rounded-full flex-shrink-0 mt-1"
                                         style="background:{{ $evt['color'] }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-medium text-[#dde6f0]">{{ $evt['title'] }}</p>
                                            <flux:badge
                                                size="sm"
                                                :color="match($evt['type']) {
                                                    'booking'   => 'purple',
                                                    'milestone' => 'green',
                                                    default     => 'blue',
                                                }"
                                            >{{ ucfirst($evt['type']) }}</flux:badge>
                                            @if($evt['overdue'])
                                                <flux:badge size="sm" color="red">Overdue</flux:badge>
                                            @endif
                                        </div>
                                        <p class="text-xs text-[#506070] mt-0.5">{{ $evt['meta'] }}</p>
                                    </div>
                                    @if($evt['time'])
                                        <span class="text-xs font-mono text-[#506070] flex-shrink-0">
                                            {{ $evt['time'] }}@if($evt['time_end'] ?? null) – {{ $evt['time_end'] }}@endif
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Sidebar: upcoming ───────────────────────────────── --}}
        <div class="w-60 flex-shrink-0 hidden lg:block space-y-4">

            {{-- Legend --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-4 space-y-2">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">Legend</p>
                @foreach([
                    ['color' => '#60a5fa', 'label' => 'Task due'],
                    ['color' => '#7EE8A2', 'label' => 'Milestone'],
                    ['color' => '#a78bfa', 'label' => 'Confirmed booking'],
                    ['color' => '#fbbf24', 'label' => 'Pending booking'],
                ] as $l)
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-sm flex-shrink-0" style="background:{{ $l['color'] }}"></div>
                        <span class="text-xs text-[#8da0b8]">{{ $l['label'] }}</span>
                    </div>
                @endforeach
            </flux:card>

            {{-- Upcoming --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-4">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-3">Upcoming</p>
                @forelse($this->upcoming as $item)
                    <div class="flex items-start gap-2.5 mb-3 last:mb-0">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1.5"
                             style="background:{{ $item['color'] }}"></div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-[#dde6f0] leading-snug truncate">
                                {{ $item['title'] }}
                            </p>
                            <p class="text-[10px] text-[#506070] mt-0.5">
                                {{ $item['date'] }} · {{ $item['meta'] }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-[#506070]">Nothing upcoming.</p>
                @endforelse
            </flux:card>

        </div>
    </div>
</div>

{{-- Task drawer --}}
@if($openTaskId)
    <div class="fixed inset-0 z-40 flex justify-end" wire:key="cal-drawer-{{ $openTaskId }}">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeTask"></div>
        <div class="relative z-50 w-full max-w-xl bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
             style="animation:slideIn .25s ease both">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <flux:heading>Task Detail</flux:heading>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeTask"/>
            </div>
            <div class="p-5">
                @livewire('tasks.task-detail', ['taskId' => $openTaskId], key('cal-'.$openTaskId))
            </div>
        </div>
    </div>
    <style>
        @keyframes slideIn { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
    </style>
@endif
