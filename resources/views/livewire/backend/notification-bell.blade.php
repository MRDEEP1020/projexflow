<div class="relative">
    {{-- Flux Dropdown (Free version) --}}
    <flux:dropdown>

        {{-- 1. The Trigger (Bell Button) --}}
        <flux:button
            class="relative !p-0 w-9 h-9 rounded-lg"
            variant="ghost"
            icon="bell"
        >
            @if($unreadCount > 0)
                <flux:badge
                    class="absolute -top-1 -right-1 min-w-[17px] h-[17px] !rounded-full !bg-[var(--danger)] border-2 border-[var(--bg)]"
                    size="xs"
                >
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </flux:badge>
            @endif
        </flux:button>

        <flux:menu class="w-[320px] !p-0">

            <div class="flex items-center justify-between px-3.5 py-3 border-b border-[var(--border)]">
                <flux:heading size="sm" class="font-['Syne',sans-serif] text-[13px] font-semibold text-[var(--text)]">
                    Notifications
                </flux:heading>

                @if($unreadCount > 0)
                    <flux:menu.item
                        wire:click="markAllRead"
                        class="!p-0 text-[11.5px] hover:opacity-75 transition-opacity"
                        style="color: var(--accent); justify-content: flex-end;"
                    >
                        Mark all read
                    </flux:menu.item>
                @endif
            </div>

            <div class="max-h-[380px] overflow-y-auto [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-track]:bg-[var(--surface2)] [&::-webkit-scrollbar-track]:rounded-full [&::-webkit-scrollbar-thumb]:bg-[var(--border)] [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-[var(--border2)]">
                @forelse($notifications as $notification)
                    <flux:menu.item
                        wire:click="markRead({{ $notification['id'] }})"
                        @click="@js($notification['url']) && (window.location.href = @js($notification['url']))"
                        class="!px-3.5 !py-2.5 !rounded-none {{ !$notification['read_at'] ? 'bg-[rgba(126,232,162,0.03)]' : '' }}"
                    >
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 shrink-0 rounded-md flex items-center justify-center bg-[var(--surface2)] border border-[var(--border)] mt-0.5">
                                @switch($notification['type'])
                                    @case('task_assigned')
                                        <flux:icon.check-circle class="text-blue-400" style="width: 14px; height: 14px;" />
                                        @break
                                    @case('pr_merged')
                                        <flux:icon.git-pull-request class="text-purple-400" style="width: 14px; height: 14px;" />
                                        @break
                                    @case('booking_confirmed')
                                        <flux:icon.calendar class="text-emerald-400" style="width: 14px; height: 14px;" />
                                        @break
                                    @case('payment_released')
                                        <flux:icon.currency-dollar class="text-[var(--accent)]" style="width: 14px; height: 14px;" />
                                        @break
                                    @case('dispute_opened')
                                        <flux:icon.exclamation-triangle class="text-red-400" style="width: 14px; height: 14px;" />
                                        @break
                                    @default
                                        <flux:icon.bell class="text-[var(--dim)]" style="width: 14px; height: 14px;" />
                                @endswitch
                            </div>

                            <div class="flex-1 min-w-0">
                                <flux:text class="block text-[12.5px] font-medium leading-tight text-[var(--text)]">
                                    {{ $notification['title'] }}
                                    @if(!$notification['read_at'])
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-[var(--accent)] ml-1.5 align-middle"></span>
                                    @endif
                                </flux:text>

                                @if($notification['body'])
                                    <flux:text class="block text-[11.5px] mt-0.5 truncate" color="dim">
                                        {{ $notification['body'] }}
                                    </flux:text>
                                @endif

                                <flux:text class="block text-[11px] mt-1" color="muted">
                                    {{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                    </flux:menu.item>
                @empty
                    <div class="flex flex-col items-center gap-2.5 py-8 px-4">
                        <flux:icon.bell class="text-[var(--muted)]" style="width: 28px; height: 28px;" />
                        <flux:text color="muted">All caught up!</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:menu>
    </flux:dropdown>
</div>