<div class="max-w-4xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Bookings</flux:heading>
            <flux:text class="mt-0.5">Manage session requests and confirmed meetings.</flux:text>
        </div>
        <flux:button
            variant="primary" size="sm" icon="link"
            x-data
            @click="navigator.clipboard.writeText('{{ route('backend.bookingPage', Auth::user()->name) }}');
                    $dispatch('toast', [{message:'Booking link copied!', type:'success'}])"
        >Copy booking link</flux:button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-[#1c2e45]">
        @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'past' => 'Past'] as $key => $label)
            <button
                wire:click="$set('tab', '{{ $key }}')"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-all"
                style="{{ $tab === $key
                    ? 'border-color:#7EE8A2;color:#7EE8A2'
                    : 'border-color:transparent;color:#8da0b8' }}"
            >
                {{ $label }}
                @if($this->counts[$key] > 0)
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono"
                          style="{{ $key === 'pending' ? 'background:rgba(239,68,68,.15);color:#f87171' : 'background:#1c2e45;color:#8da0b8' }}">
                        {{ $this->counts[$key] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Booking list --}}
    @if($this->bookings->isEmpty())
        <div class="flex flex-col items-center gap-2 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <flux:icon.calendar class="size-8 text-[#506070]"/>
            <p class="text-sm text-[#506070]">No {{ $tab }} bookings.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->bookings as $b)
                <div
                    class="flex items-start gap-4 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4 hover:border-[#254060] transition-all cursor-pointer"
                    wire:click="openDetail({{ $b->id }})"
                    wire:key="bk-{{ $b->id }}"
                >
                    {{-- Date block --}}
                    <div class="w-14 flex-shrink-0 flex flex-col items-center bg-[#131d2e] border border-[#1c2e45] rounded-xl py-2">
                        <span class="text-[10px] font-mono uppercase text-[#506070]">
                            {{ Carbon\Carbon::parse($b->start_at)->format('M') }}
                        </span>
                        <span style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:#fff;line-height:1.1">
                            {{ Carbon\Carbon::parse($b->start_at)->format('d') }}
                        </span>
                        <span class="text-[10px] font-mono text-[#7EE8A2]">
                            {{ Carbon\Carbon::parse($b->start_at)->format('H:i') }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-['Syne'] font-semibold text-sm text-white">{{ $b->title }}</p>
                            <flux:badge
                                size="sm"
                                :color="match($b->status) {
                                    'confirmed' => 'green',
                                    'pending'   => 'yellow',
                                    'cancelled' => 'red',
                                    default     => 'zinc',
                                }"
                            >{{ ucfirst($b->status) }}</flux:badge>
                        </div>
                        <p class="text-xs text-[#8da0b8] mt-1">
                            {{ $b->client_name ?? $b->client?->name ?? 'Guest' }}
                            @if($b->client_email)
                                · {{ $b->client_email }}
                            @endif
                        </p>
                        <p class="text-xs text-[#506070] mt-0.5">
                            {{ Carbon\Carbon::parse($b->start_at)->format('l, F j Y') }}
                            · {{ Carbon\Carbon::parse($b->start_at)->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Quick actions for pending --}}
                    @if($b->status === 'pending' && $b->provider_id === Auth::id())
                        <div class="flex gap-2 flex-shrink-0" wire:click.stop>
                            <flux:button
                                variant="ghost" size="sm" icon="check"
                                wire:click="confirm({{ $b->id }})"
                                class="text-[#7EE8A2] border-[#7EE8A2]/20 hover:border-[#7EE8A2]/40"
                            >Confirm</flux:button>
                            <flux:button
                                variant="ghost" size="sm" icon="x-mark"
                                wire:click="decline({{ $b->id }})"
                                class="text-red-400 border-red-500/20 hover:border-red-500/40"
                            >Decline</flux:button>
                        </div>
                    @endif

                    {{-- Join button for confirmed future bookings --}}
                    @if($b->status === 'confirmed' && Carbon\Carbon::parse($b->start_at)->isFuture() && $b->meetingRoom)
                        <a
                            href="{{ route('backend.meetingRoom', $b->meetingRoom->room_token) }}"
                            wire:navigate
                            class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold
                                bg-[#7EE8A2]/10 text-[#7EE8A2] border border-[#7EE8A2]/20 hover:bg-[#7EE8A2]/20 transition-colors"
                            wire:click.stop
                        >
                            <flux:icon.video-camera class="size-3.5"/>
                            Join meeting
                        </a>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- ── Booking detail drawer ──────────────────────────────── --}}
@if($detailId && $this->openBooking)
    @php $b = $this->openBooking; @endphp
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div class="relative z-50 w-full max-w-md bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
             style="animation:slideIn .25s ease both">

            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <flux:heading>Booking Detail</flux:heading>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeDetail"/>
            </div>

            <div class="p-5 space-y-5">

                {{-- Date/time block --}}
                <div class="flex items-center gap-3 p-4 bg-[#131d2e] border border-[#1c2e45] rounded-xl">
                    <div class="w-12 h-12 rounded-xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/15 flex flex-col items-center justify-center">
                        <span class="text-[9px] font-mono uppercase text-[#506070]">{{ Carbon\Carbon::parse($b->start_at)->format('M') }}</span>
                        <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:#fff;line-height:1">
                            {{ Carbon\Carbon::parse($b->start_at)->format('d') }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">{{ Carbon\Carbon::parse($b->start_at)->format('l, F j Y') }}</p>
                        <p class="text-xs text-[#8da0b8] mt-0.5">
                            {{ Carbon\Carbon::parse($b->start_at)->format('H:i') }}
                            – {{ Carbon\Carbon::parse($b->end_at)->format('H:i') }}
                        </p>
                        <flux:badge
                            size="sm"
                            class="mt-1"
                            :color="match($b->status) {
                                'confirmed' => 'green',
                                'pending'   => 'yellow',
                                'cancelled' => 'red',
                                default     => 'zinc',
                            }"
                        >{{ ucfirst($b->status) }}</flux:badge>
                    </div>
                </div>

                {{-- Client info --}}
                <div class="space-y-2">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Client</p>
                    <div class="flex items-center gap-2.5">
                        <flux:avatar name="{{ $b->client_name ?? 'Guest' }}" size="sm"/>
                        <div>
                            <p class="text-sm font-medium text-[#dde6f0]">{{ $b->client_name ?? $b->client?->name ?? 'Guest' }}</p>
                            @if($b->client_email)
                                <p class="text-xs text-[#506070]">{{ $b->client_email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Message --}}
                @if($b->message)
                    <div class="space-y-1.5">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Message</p>
                        <p class="text-sm text-[#8da0b8] bg-[#131d2e] rounded-xl p-3 leading-relaxed border border-[#1c2e45]">
                            {{ $b->message }}
                        </p>
                    </div>
                @endif

                {{-- Actions --}}
                @if($b->status === 'pending' && $b->provider_id === Auth::id())
                    <div class="space-y-3 pt-2 border-t border-[#1c2e45]">
                        <flux:button
                            variant="primary"
                            class="w-full"
                            wire:click="confirm({{ $b->id }})"
                            wire:loading.attr="disabled"
                            icon="check-circle"
                        >
                            <span wire:loading.remove>Confirm booking</span>
                            <span wire:loading>Confirming…</span>
                        </flux:button>

                        <flux:field>
                            <flux:label>Decline reason <span class="text-[#506070] font-normal text-xs">(optional)</span></flux:label>
                            <flux:textarea wire:model="declineNote" rows="2" placeholder="Let the client know why…"/>
                        </flux:field>

                        <flux:button
                            variant="ghost"
                            class="w-full border-red-500/20 text-red-400 hover:border-red-400"
                            wire:click="decline({{ $b->id }})"
                            icon="x-circle"
                        >Decline booking</flux:button>
                    </div>
                @endif

                {{-- Meeting room (confirmed) --}}
                @if($b->status === 'confirmed' && $b->meetingRoom)
                    <div class="space-y-3 pt-2 border-t border-[#1c2e45]">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Meeting Room</p>

                        @if(Carbon\Carbon::parse($b->start_at)->isFuture())
                            <a
                                href="{{ route('backend.meetingRoom', $b->meetingRoom->room_token) }}"
                                wire:navigate
                                class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold
                                    bg-[#7EE8A2]/10 text-[#7EE8A2] border border-[#7EE8A2]/20 hover:bg-[#7EE8A2]/20 transition-colors"
                            >
                                <flux:icon.video-camera class="size-4"/>
                                Join meeting
                            </a>
                        @endif

                        {{-- Recording --}}
                        @if($b->meetingRoom->recordings?->isNotEmpty())
                            @foreach($b->meetingRoom->recordings as $rec)
                                @if(! $rec->is_processing && $rec->recording_url)
                                    <div class="space-y-2">
                                        <a href="{{ $rec->recording_url }}" target="_blank"
                                           class="flex items-center gap-2 text-xs text-[#8da0b8] hover:text-[#7EE8A2] transition-colors">
                                            <flux:icon.play-circle class="size-4"/>
                                            View recording
                                        </a>
                                        @if($rec->transcript_url)
                                            <a href="{{ $rec->transcript_url }}" target="_blank"
                                               class="flex items-center gap-2 text-xs text-[#8da0b8] hover:text-[#7EE8A2] transition-colors">
                                                <flux:icon.document-text class="size-4"/>
                                                View transcript
                                            </a>
                                        @endif
                                    </div>
                                @elseif($rec->is_processing)
                                    <p class="text-xs text-[#506070] flex items-center gap-1.5">
                                        <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                        Processing recording…
                                    </p>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
    </style>
@endif
