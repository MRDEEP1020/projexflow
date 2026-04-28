<div class="max-w-4xl space-y-5">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Notifications</flux:heading>
        @if($this->counts['unread'] > 0)
            <flux:button variant="ghost" size="sm" wire:click="markAllAsRead">
                Mark all as read
            </flux:button>
        @endif
    </div>

    {{-- Search + type filter --}}
    <div class="flex gap-3 flex-wrap">
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search notifications…"
                icon="magnifying-glass" clearable/>
        </div>

        <flux:select wire:model.live="type" class="w-44">
            @foreach($this->notificationTypes as $val => $label)
                <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        @if($search || $type !== 'all')
            <flux:button variant="ghost" size="sm"
                wire:click="$reset(['search','type']); $reset('page')">
                Clear filters
            </flux:button>
        @endif
    </div>

    {{-- Tabs ─────────────────────────────────────────────────── --}}
    <div class="flex gap-1 border-b border-[#1c2e45]">
        @foreach(['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $key => $label)
            <button wire:click="$set('filter','{{ $key }}'); $reset('page')"
                class="flex items-center gap-1.5 px-4 py-2 text-sm border-b-2 -mb-px transition-all"
                style="{{ $filter === $key ? 'border-color:#7EE8A2;color:#7EE8A2' : 'border-color:transparent;color:#8da0b8' }}">
                {{ $label }}
                @if($this->counts[$key] > 0)
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded"
                          style="{{ $key === 'unread'
                              ? 'background:rgba(126,232,162,.15);color:#7EE8A2'
                              : 'background:#1c2e45;color:#8da0b8' }}">
                        {{ $this->counts[$key] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Notifications list ─────────────────────────────────── --}}
    @if($this->notifications->isEmpty())
        <div class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <flux:icon.bell-slash class="size-8 text-[#506070]"/>
            <flux:heading>No notifications</flux:heading>
            <flux:text class="text-sm">
                @if($filter === 'read')
                    You haven't read any notifications yet.
                @elseif($filter === 'unread')
                    You're all caught up!
                @else
                    You don't have any notifications.
                @endif
            </flux:text>
        </div>
    @else
        <div class="space-y-2">
            @foreach($this->notifications as $n)
                <div class="flex items-start gap-3 p-4 rounded-xl border transition-all group
                    {{ $n->read_at
                        ? 'bg-[#080c14] border-[#1c2e45] hover:border-[#254060]'
                        : 'bg-[#0e1420] border-[#7EE8A2]/20 hover:border-[#7EE8A2]/40' }}"
                     wire:key="notif-{{ $n->id }}">

                    {{-- Avatar / icon --}}
                    <div class="w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center
                        {{ $n->read_at ? 'bg-[#1c2e45]' : 'bg-[#7EE8A2]/10 text-[#7EE8A2]' }}">
                        @php
                            $icon = match($n->type) {
                                'booking_request','booking_confirmed' => 'calendar',
                                'work_submitted' => 'document-check',
                                'payment_released','payment_auto_released' => 'banknotes',
                                'new_review' => 'star',
                                'recording_ready','transcript_ready' => 'video-camera',
                                'github_push' => 'code-bracket',
                                'contract_created' => 'document-text',
                                'withdrawal_completed' => 'arrow-right-on-rectangle',
                                default => 'bell',
                            };
                        @endphp
                        <flux:icon :name="$icon" class="size-5"/>
                    </div>

                    {{-- Content ─────────────────────────────────── --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start gap-2 flex-wrap">
                            <h3 class="text-sm font-semibold text-[#dde6f0]">{{ $n->title }}</h3>
                            @if(!$n->read_at)
                                <span class="w-2 h-2 rounded-full bg-[#7EE8A2] flex-shrink-0 mt-1.5"></span>
                            @endif
                        </div>
                        @if($n->body)
                            <p class="text-sm text-[#8da0b8] mt-0.5 line-clamp-2">{{ $n->body }}</p>
                        @endif
                        <p class="text-xs text-[#506070] mt-1">
                            {{ $n->created_at->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Actions ──────────────────────────────────── --}}
                    <div class="flex gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        @if($n->url)
                            <a href="{{ $n->url }}" wire:navigate
                               class="p-1.5 rounded-lg hover:bg-[#1c2e45] text-[#506070] hover:text-[#dde6f0] transition-colors">
                                <flux:icon.arrow-top-right-on-square class="size-4"/>
                            </a>
                        @endif
                        @if(!$n->read_at)
                            <button wire:click="markAsRead({{ $n->id }})"
                                class="p-1.5 rounded-lg hover:bg-[#1c2e45] text-[#506070] hover:text-[#dde6f0] transition-colors"
                                title="Mark as read">
                                <flux:icon.check-circle class="size-4"/>
                            </button>
                        @endif
                        <button wire:click="delete({{ $n->id }})"
                            class="p-1.5 rounded-lg hover:bg-red-500/10 text-[#506070] hover:text-red-400 transition-colors"
                            title="Delete">
                            <flux:icon.trash class="size-4"/>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-5">
            {{ $this->notifications->links(data: ['using' => 'bootstrap-5']) }}
        </div>

        {{-- Delete all (if showing read) ──────────────────────── --}}
        @if($this->counts['read'] > 0)
            <div class="flex justify-center pt-4 border-t border-[#1c2e45]">
                <flux:button variant="ghost" size="sm" wire:click="deleteAll" icon="trash"
                    class="text-[#506070] hover:text-red-400">
                    Delete all notifications
                </flux:button>
            </div>
        @endif
    @endif
</div>
