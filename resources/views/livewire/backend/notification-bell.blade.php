<div class="bell-wrap" x-data="{ open: false }">

    <button @click="open = !open" class="bell-btn" :aria-expanded="open" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 01-3.46 0"/>
        </svg>
        @if($unreadCount > 0)
            <span class="bell-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div class="bell-dropdown" x-show="open" @click.away="open=false" x-transition>

        <div class="bell-header">
            <span class="bell-title">Notifications</span>
            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="bell-mark-all">Mark all read</button>
            @endif
        </div>

        <div class="bell-list">
            @forelse($notifications as $n)
                <button
                    wire:click="markRead({{ $n->id }})"
                    @click="open=false; @if($n->url) window.location.href='{{ $n->url }}'; @endif"
                    class="bell-item {{ $n->isUnread() ? 'is-unread' : '' }}"
                >
                    <div class="bell-item-icon">
                        {{-- Icon based on notification type --}}
                        @switch($n->type)
                            @case('task_assigned')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                                @break
                            @case('pr_merged')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2" stroke-linecap="round"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M6 21V9a9 9 0 009 9"/></svg>
                                @break
                            @case('booking_confirmed')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                @break
                            @case('payment_released')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7EE8A2" stroke-width="2" stroke-linecap="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                                @break
                            @case('dispute_opened')
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                @break
                            @default
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--dim)" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                        @endswitch
                    </div>
                    <div class="bell-item-content">
                        <span class="bell-item-title">{{ $n->title }}</span>
                        @if($n->body)
                            <span class="bell-item-body">{{ $n->body }}</span>
                        @endif
                        <span class="bell-item-time">{{ $n->created_at->diffForHumans() }}</span>
                    </div>
                    @if($n->isUnread())
                        <span class="bell-unread-dot"></span>
                    @endif
                </button>
            @empty
                <div class="bell-empty">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" stroke-linecap="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    <span>All caught up!</span>
                </div>
            @endforelse
        </div>

    </div>

</div>

<style>
.bell-wrap { position: relative; }
.bell-btn { position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:none;border:none;color:var(--dim);cursor:pointer;border-radius:8px;transition:all .15s; }
.bell-btn:hover { background:var(--surface2);color:var(--text); }
.bell-badge { position:absolute;top:3px;right:3px;min-width:17px;height:17px;padding:0 4px;background:var(--danger);border-radius:10px;font-family:'Inter',sans-serif;font-size:9.5px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;border:2px solid var(--bg); }
.bell-dropdown { position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--surface);border:1px solid var(--border);border-radius:13px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,.4);z-index:100; }
.bell-header { display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--border); }
.bell-title { font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:var(--text); }
.bell-mark-all { background:none;border:none;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--accent);cursor:pointer;padding:0;transition:opacity .15s; }
.bell-mark-all:hover { opacity:.75; }
.bell-list { max-height:380px;overflow-y:auto; }
.bell-item { display:flex;align-items:flex-start;gap:10px;width:100%;padding:11px 14px;background:none;border:none;cursor:pointer;text-align:left;transition:background .12s;position:relative; }
.bell-item:hover { background:var(--surface2); }
.bell-item.is-unread { background:rgba(126,232,162,.03); }
.bell-item-icon { width:28px;height:28px;flex-shrink:0;border-radius:7px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin-top:1px; }
.bell-item-content { flex:1;min-width:0; }
.bell-item-title { display:block;font-family:'Inter',sans-serif;font-size:12.5px;font-weight:500;color:var(--text);line-height:1.4; }
.bell-item-body { display:block;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--dim);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.bell-item-time { display:block;font-family:'Inter',sans-serif;font-size:11px;color:var(--muted);margin-top:3px; }
.bell-unread-dot { position:absolute;right:14px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0; }
.bell-empty { display:flex;flex-direction:column;align-items:center;gap:10px;padding:32px;color:var(--muted);font-family:'Inter',sans-serif;font-size:13px; }
</style>
