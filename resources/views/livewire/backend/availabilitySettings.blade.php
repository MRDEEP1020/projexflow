<div class="max-w-3xl space-y-6">

    <div>
        <flux:heading size="xl">Availability Settings</flux:heading>
        <flux:text class="mt-1">Configure when clients can book sessions with you.</flux:text>
    </div>

    {{-- ── Weekly schedule ────────────────────────────────── --}}
    <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Weekly Schedule</flux:heading>
            <div class="flex items-center gap-2">
                <flux:text class="text-xs">Slot duration</flux:text>
                <flux:select wire:model="slotMinutes" class="w-28" size="sm">
                    <flux:select.option value="15">15 min</flux:select.option>
                    <flux:select.option value="30">30 min</flux:select.option>
                    <flux:select.option value="45">45 min</flux:select.option>
                    <flux:select.option value="60">60 min</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="space-y-2">
            @foreach([0=>'Monday',1=>'Tuesday',2=>'Wednesday',3=>'Thursday',4=>'Friday',5=>'Saturday',6=>'Sunday'] as $day => $label)
                <div class="flex items-center gap-3 p-3 rounded-xl border
                    {{ $schedule[$day]['available']
                        ? 'border-[#1c2e45] bg-[#080c14]'
                        : 'border-[#1c2e45] bg-[#080c14] opacity-60' }}"
                     wire:key="day-{{ $day }}">

                    {{-- Toggle --}}
                    <flux:switch wire:model.live="schedule.{{ $day }}.available"/>

                    {{-- Day name --}}
                    <span class="w-24 text-sm font-medium {{ $schedule[$day]['available'] ? 'text-[#dde6f0]' : 'text-[#506070]' }}">
                        {{ $label }}
                    </span>

                    @if($schedule[$day]['available'])
                        {{-- Time range --}}
                        <div class="flex items-center gap-2 flex-1">
                            <flux:input
                                type="time"
                                wire:model="schedule.{{ $day }}.start_time"
                                size="sm"
                                class="w-28"
                            />
                            <span class="text-[#506070] text-xs">to</span>
                            <flux:input
                                type="time"
                                wire:model="schedule.{{ $day }}.end_time"
                                size="sm"
                                class="w-28"
                            />
                        </div>
                        {{-- Slot count preview --}}
                        @php
                            try {
                                $s   = \Carbon\Carbon::createFromFormat('H:i', $schedule[$day]['start_time']);
                                $e   = \Carbon\Carbon::createFromFormat('H:i', $schedule[$day]['end_time']);
                                $cnt = $s < $e ? (int)(($e->diffInMinutes($s)) / $slotMinutes) : 0;
                            } catch (\Exception $ex) { $cnt = 0; }
                        @endphp
                        <span class="text-[11px] text-[#506070] font-mono w-16 text-right">
                            {{ $cnt }} slots
                        </span>
                    @else
                        <span class="text-xs text-[#506070] flex-1">Unavailable</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex justify-end pt-2 border-t border-[#1c2e45]">
            <flux:button variant="primary" wire:click="saveSchedule" wire:loading.attr="disabled">
                <span wire:loading.remove>Save schedule</span>
                <span wire:loading>Saving…</span>
            </flux:button>
        </div>
    </flux:card>

    {{-- ── Date overrides ──────────────────────────────────── --}}
    <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
        <div>
            <flux:heading size="lg">Date Overrides</flux:heading>
            <flux:text class="mt-1">Block a specific day off or open a normally-closed day.</flux:text>
        </div>

        {{-- Add override form --}}
        <div class="p-4 rounded-xl border border-[#1c2e45] bg-[#080c14] space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Date <<span class="text-red-400">*</span>/></flux:label>
                    <flux:input type="date" wire:model="overrideDate" min="{{ today()->toDateString() }}"/>
                    <flux:error name="overrideDate"/>
                </flux:field>

                <flux:field>
                    <flux:label>Override type</flux:label>
                    <div class="flex gap-3 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="overrideAvailable" value="0" class="sr-only">
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all
                                {{ !$overrideAvailable
                                    ? 'border-red-500/40 bg-red-500/10 text-red-400'
                                    : 'border-[#1c2e45] text-[#506070]' }}">
                                <flux:icon.x-circle class="size-3.5"/>
                                Block day
                            </div>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="overrideAvailable" value="1" class="sr-only">
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all
                                {{ $overrideAvailable
                                    ? 'border-[#7EE8A2]/40 bg-[#7EE8A2]/10 text-[#7EE8A2]'
                                    : 'border-[#1c2e45] text-[#506070]' }}">
                                <flux:icon.check-circle class="size-3.5"/>
                                Open day
                            </div>
                        </label>
                    </div>
                </flux:field>
            </div>

            @if($overrideAvailable)
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Start time</flux:label>
                        <flux:input type="time" wire:model="overrideStart"/>
                        <flux:error name="overrideStart"/>
                    </flux:field>
                    <flux:field>
                        <flux:label>End time</flux:label>
                        <flux:input type="time" wire:model="overrideEnd"/>
                        <flux:error name="overrideEnd"/>
                    </flux:field>
                </div>
            @endif

            <flux:field>
                <flux:label>Reason <span class="text-[#506070] font-normal text-xs">(optional)</span></flux:label>
                <flux:input wire:model="overrideReason" placeholder="e.g. Public holiday, Conference…"/>
            </flux:field>

            <flux:button variant="primary" size="sm" wire:click="addOverride">
                Add override
            </flux:button>
        </div>

        {{-- Existing overrides list --}}
        @if($this->upcomingOverrides->isNotEmpty())
            <div class="divide-y divide-[#1c2e45] border border-[#1c2e45] rounded-xl overflow-hidden">
                @foreach($this->upcomingOverrides as $ov)
                    <div class="flex items-center gap-3 px-4 py-3" wire:key="ov-{{ $ov->id }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                            {{ $ov->is_available
                                ? 'bg-[#7EE8A2]/10 text-[#7EE8A2]'
                                : 'bg-red-500/10 text-red-400' }}">
                            @if($ov->is_available)
                                <flux:icon.check class="size-4"/>
                            @else
                                <flux:icon.x-mark class="size-4"/>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-[#dde6f0]">
                                {{ \Carbon\Carbon::parse($ov->date)->format('l, M j Y') }}
                            </p>
                            <p class="text-xs text-[#506070]">
                                @if($ov->is_available)
                                    Open {{ $ov->start_time }} – {{ $ov->end_time }}
                                @else
                                    Blocked{{ $ov->reason ? ' — ' . $ov->reason : '' }}
                                @endif
                            </p>
                        </div>
                        <flux:button
                            variant="ghost" size="sm" icon="trash"
                            wire:click="deleteOverride({{ $ov->id }})"
                            class="text-[#506070] hover:text-red-400"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- ── 7-day preview ───────────────────────────────────── --}}
    <flux:card class="bg-[#0e1420] border-[#1c2e45]">
        <flux:heading size="sm" class="mb-3">Next 7 Days Preview</flux:heading>
        <div class="grid grid-cols-7 gap-2">
            @foreach($this->previewSlots as $day)
                <div class="flex flex-col items-center gap-1 p-2 rounded-lg border
                    {{ $day['available']
                        ? 'border-[#7EE8A2]/20 bg-[#7EE8A2]/04'
                        : 'border-[#1c2e45] bg-[#080c14] opacity-50' }}">
                    <p class="text-[10px] font-mono text-[#506070]">{{ substr($day['date'], 0, 3) }}</p>
                    <p class="text-[11px] text-[#8da0b8]">{{ substr($day['date'], 4, 6) }}</p>
                    @if($day['available'])
                        <flux:badge size="sm" color="green">{{ $day['count'] }}</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">Off</flux:badge>
                    @endif
                </div>
            @endforeach
        </div>
        <flux:text class="text-xs mt-3">Numbers show available booking slots. Clients see this on your public booking page.</flux:text>
    </flux:card>

</div>
