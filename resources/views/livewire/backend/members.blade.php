<x-slot name="header">Members</x-slot>

<div class="members-wrap">

    {{-- Header row --}}
    <div class="members-header">
        <div>
            <h1 class="members-title">{{ $org->name }}</h1>
            <p class="members-sub">{{ $members->count() }} member{{ $members->count() !== 1 ? 's' : '' }} · {{ $pendingInvites->count() }} pending invite{{ $pendingInvites->count() !== 1 ? 's' : '' }}</p>
        </div>
        <button wire:click="$toggle('showInviteForm')" class="btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Invite member
        </button>
    </div>

    {{-- Invite form --}}
    @if($showInviteForm)
        <div class="invite-form" wire:key="invite-form">
            <form wire:submit="sendInvite" novalidate>
                <div class="invite-form-inner">
                    <div class="invite-email-wrap {{ $errors->has('inviteEmail') ? 'has-error' : '' }}">
                        <div class="field-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="email" wire:model="inviteEmail" class="invite-email-input" placeholder="colleague@company.com" autofocus>
                    </div>

                    <select wire:model="inviteRole" class="invite-role-select">
                        <option value="admin">Admin</option>
                        <option value="project_manager">Project Manager</option>
                        <option value="member" selected>Member</option>
                        <option value="viewer">Viewer</option>
                    </select>

                    <div class="invite-actions">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Send invite</span>
                            <span wire:loading>Sending…</span>
                        </button>
                        <button type="button" wire:click="$set('showInviteForm', false)" class="btn-ghost">Cancel</button>
                    </div>
                </div>
                @error('inviteEmail')
                    <p class="field-error" style="margin-top:8px">{{ $message }}</p>
                @enderror
            </form>
        </div>
    @endif

    {{-- Current members table --}}
    <div class="members-section">
        <h2 class="section-label">Current members</h2>
        <div class="members-table">
            @foreach($members as $membership)
                <div class="member-row" wire:key="member-{{ $membership->id }}">
                    <div class="member-avatar-wrap">
                        <img src="{{ $membership->user->avatar_url }}" alt="{{ $membership->user->name }}" class="member-avatar">
                        <div class="member-info">
                            <span class="member-name">{{ $membership->user->name }}</span>
                            <span class="member-email">{{ $membership->user->email }}</span>
                        </div>
                    </div>

                    <div class="member-meta">
                        {{-- Role selector (admin can change non-owner roles) --}}
                        @if($membership->role === 'owner')
                            <span class="role-badge role-owner">Owner</span>
                        @elseif($membership->user_id === Auth::id())
                            <span class="role-badge role-{{ $membership->role }}">{{ ucfirst(str_replace('_', ' ', $membership->role)) }}</span>
                        @else
                            <select
                                wire:change="changeRole({{ $membership->id }}, $event.target.value)"
                                class="role-select"
                            >
                                @foreach(['admin' => 'Admin', 'project_manager' => 'Project Manager', 'member' => 'Member', 'viewer' => 'Viewer'] as $val => $label)
                                    <option value="{{ $val }}" {{ $membership->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Remove button (not for owner, not for self if last admin) --}}
                        @if($membership->role !== 'owner' && $membership->user_id !== Auth::id())
                            <button
                                wire:click="confirmRemove({{ $membership->id }}, '{{ $membership->user->name }}')"
                                class="member-remove"
                                title="Remove from organization"
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pending invitations --}}
    @if($pendingInvites->isNotEmpty())
        <div class="members-section" style="margin-top:28px">
            <h2 class="section-label">Pending invitations</h2>
            <div class="members-table">
                @foreach($pendingInvites as $invite)
                    <div class="member-row" wire:key="invite-{{ $invite->id }}">
                        <div class="member-avatar-wrap">
                            <div class="invite-avatar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.8" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="member-info">
                                <span class="member-name">{{ $invite->email }}</span>
                                <span class="member-email">Invited by {{ $invite->invitedBy->name }} · expires {{ $invite->expires_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="member-meta">
                            <span class="role-badge role-{{ $invite->role }}">{{ ucfirst(str_replace('_', ' ', $invite->role)) }}</span>
                            <span class="invite-pending-badge">Pending</span>
                            <button wire:click="resendInvite({{ $invite->id }})" class="btn-icon" title="Resend invitation">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                            </button>
                            <button wire:click="cancelInvite({{ $invite->id }})" class="member-remove" title="Cancel invitation">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Remove confirmation modal --}}
    @if($removingMemberId)
        <div class="modal-overlay" wire:click.self="$set('removingMemberId', null)">
            <div class="modal-box">
                <h3 class="modal-title">Remove member</h3>
                <p class="modal-body">
                    Are you sure you want to remove <strong>{{ $removingMemberName }}</strong> from <strong>{{ $org->name }}</strong>?
                    They will lose access to all projects in this organization and their assigned tasks will be unassigned.
                </p>
                <div class="modal-actions">
                    <button type="button" wire:click="$set('removingMemberId', null)" class="btn-ghost">Cancel</button>
                    <button type="button" wire:click="removeMember" class="btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove>Remove member</span>
                        <span wire:loading>Removing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>

<style>
.members-wrap{max-width:720px}
.members-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px}
.members-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff;margin:0 0 4px}
.members-sub{font-family:'Inter',sans-serif;font-size:13px;color:var(--muted);margin:0}
.btn-primary{display:flex;align-items:center;gap:7px;padding:9px 16px;background:var(--accent);border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#080c14;cursor:pointer;transition:all .15s;white-space:nowrap}
.btn-primary:hover:not(:disabled){background:var(--accent2)}
.btn-primary:disabled{opacity:.7;cursor:not-allowed}
.invite-form{background:var(--surface);border:1px solid var(--border2);border-radius:12px;padding:18px;margin-bottom:20px;animation:fadeDown .2s ease}
@keyframes fadeDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.invite-form-inner{display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap}
.invite-email-wrap{flex:1;min-width:200px;position:relative;display:flex;align-items:center;background:var(--bg);border:1px solid var(--border);border-radius:9px}
.invite-email-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(126,232,162,.1)}
.invite-email-wrap.has-error{border-color:#ef4444}
.field-icon{position:absolute;left:13px;color:var(--muted);display:flex;align-items:center;pointer-events:none}
.invite-email-input{width:100%;background:transparent;border:none;outline:none;padding:11px 13px 11px 40px;font-family:'Inter',sans-serif;font-size:13.5px;color:var(--text);caret-color:var(--accent)}
.invite-email-input::placeholder{color:var(--muted)}
.invite-role-select{padding:11px 13px;background:var(--bg);border:1px solid var(--border);border-radius:9px;font-family:'Inter',sans-serif;font-size:13px;color:var(--text);cursor:pointer;outline:none}
.invite-role-select:focus{border-color:var(--accent)}
.invite-actions{display:flex;gap:8px}
.field-error{font-family:'Inter',sans-serif;font-size:11.5px;color:#f87171;margin:0}
.btn-ghost{display:flex;align-items:center;gap:6px;padding:9px 14px;background:none;border:1px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;color:var(--dim);cursor:pointer;transition:all .15s}
.btn-ghost:hover{border-color:var(--border2);color:var(--text)}
.members-section{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.section-label{font-family:'DM Mono',monospace;font-size:10.5px;text-transform:uppercase;letter-spacing:1.2px;color:var(--muted);padding:14px 18px 10px;border-bottom:1px solid var(--border)}
.members-table{display:flex;flex-direction:column}
.member-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border)}
.member-row:last-child{border-bottom:none}
.member-row:hover{background:var(--surface2)}
.member-avatar-wrap{display:flex;align-items:center;gap:12px;flex:1;min-width:0}
.member-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--border2);flex-shrink:0}
.invite-avatar{width:36px;height:36px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.member-info{min-width:0}
.member-name{display:block;font-family:'Inter',sans-serif;font-size:13.5px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.member-email{display:block;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.member-meta{display:flex;align-items:center;gap:10px;flex-shrink:0}
.role-badge{font-family:'DM Mono',monospace;font-size:10.5px;padding:3px 9px;border-radius:6px;white-space:nowrap}
.role-owner{background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2)}
.role-admin{background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.15)}
.role-project_manager{background:rgba(96,165,250,.08);color:#60a5fa;border:1px solid rgba(96,165,250,.15)}
.role-member{background:rgba(126,232,162,.07);color:var(--accent);border:1px solid rgba(126,232,162,.15)}
.role-viewer{background:rgba(100,116,139,.1);color:var(--muted);border:1px solid var(--border)}
.role-select{padding:5px 10px;background:var(--bg);border:1px solid var(--border);border-radius:7px;font-family:'Inter',sans-serif;font-size:12.5px;color:var(--text);cursor:pointer;outline:none}
.role-select:focus{border-color:var(--accent)}
.invite-pending-badge{font-family:'DM Mono',monospace;font-size:10.5px;padding:3px 9px;border-radius:6px;background:rgba(245,158,11,.08);color:#fbbf24;border:1px solid rgba(245,158,11,.15)}
.member-remove{display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:none;border:1px solid var(--border);border-radius:7px;color:var(--muted);cursor:pointer;transition:all .15s}
.member-remove:hover{border-color:#ef4444;color:#f87171;background:rgba(239,68,68,.06)}
.btn-icon{display:flex;align-items:center;justify-content:center;width:30px;height:30px;background:none;border:1px solid var(--border);border-radius:7px;color:var(--muted);cursor:pointer;transition:all .15s}
.btn-icon:hover{border-color:var(--border2);color:var(--text)}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{width:100%;max-width:420px;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;animation:fadeUp .25s ease both}
.modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#fff;margin:0 0 12px}
.modal-body{font-family:'Inter',sans-serif;font-size:13.5px;color:var(--dim);line-height:1.6;margin:0}
.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.btn-danger{display:flex;align-items:center;gap:7px;padding:9px 16px;background:var(--danger);border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#fff;cursor:pointer;transition:all .15s}
.btn-danger:hover:not(:disabled){background:#dc2626}
.btn-danger:disabled{opacity:.7;cursor:not-allowed}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
</style>
