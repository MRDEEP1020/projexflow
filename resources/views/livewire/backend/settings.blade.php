<x-slot name="header">Organization Settings</x-slot>

<div class="settings-wrap">

    {{-- Tabs --}}
    <div class="settings-tabs">
        <button wire:click="$set('activeTab','general')" class="tab-btn {{ $activeTab === 'general' ? 'active' : '' }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            General
        </button>
        <button wire:click="$set('activeTab','danger')" class="tab-btn {{ $activeTab === 'danger' ? 'active tab-danger' : '' }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Danger Zone
        </button>
    </div>

    {{-- ── General tab ───────────────────────────────── --}}
    @if($activeTab === 'general')
        <div class="settings-section" wire:key="general">

            <div class="settings-section-header">
                <h2 class="settings-section-title">General Information</h2>
                <p class="settings-section-sub">Update your organization's display name and logo.</p>
            </div>

            <form wire:submit="saveGeneral" novalidate>

                {{-- Current logo --}}
                <div class="field-group">
                    <label class="field-label">Organization Logo</label>
                    <div class="logo-section">
                        <div class="current-logo">
                            @if($org->logo)
                                <img src="{{ Storage::url($org->logo) }}" alt="{{ $org->name }}" class="logo-preview">
                                <button type="button" wire:click="removeLogo" class="logo-remove-btn">Remove</button>
                            @elseif($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="logo-preview">
                            @else
                                <div class="logo-placeholder">
                                    <span>{{ strtoupper(substr($org->name, 0, 2)) }}</span>
                                </div>
                            @endif
                        </div>
                        <label class="logo-upload-btn">
                            <input type="file" wire:model="logo" accept="image/*" class="sr-only">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload new logo
                        </label>
                    </div>
                    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                {{-- Name --}}
                <div class="field-group">
                    <label class="field-label" for="org-name">Organization name <span class="req">*</span></label>
                    <div class="field-wrap {{ $errors->has('name') ? 'has-error' : '' }}">
                        <input type="text" id="org-name" wire:model="name" class="field-input-plain" placeholder="My Organization">
                    </div>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                {{-- Plan (read-only) --}}
                <div class="field-group">
                    <label class="field-label">Plan</label>
                    <div class="plan-badge plan-{{ $org->plan }}">
                        {{ ucfirst($org->plan) }}
                        @if($org->plan === 'free')
                            <a href="#" class="plan-upgrade">Upgrade →</a>
                        @endif
                    </div>
                </div>

                {{-- Org slug (read-only) --}}
                <div class="field-group">
                    <label class="field-label">Organization slug</label>
                    <div class="field-readonly">{{ $org->slug }}</div>
                    <p class="field-hint-inline">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        Slugs cannot be changed after creation.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save changes</span>
                        <span wire:loading class="loading-inner">
                            <svg class="spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#080c14" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            Saving…
                        </span>
                    </button>
                </div>

            </form>
        </div>
    @endif

    {{-- ── Danger Zone tab ────────────────────────────── --}}
    @if($activeTab === 'danger')
        <div class="settings-section" wire:key="danger">

            <div class="settings-section-header">
                <h2 class="settings-section-title" style="color:#f87171">Danger Zone</h2>
                <p class="settings-section-sub">Actions here are permanent and cannot be undone.</p>
            </div>

            <div class="danger-card">
                <div class="danger-info">
                    <h3 class="danger-title">Delete this organization</h3>
                    <p class="danger-desc">
                        This will permanently delete <strong>{{ $org->name }}</strong>, all its projects, tasks, files, and member data.
                        You must archive all active projects before deleting.
                    </p>
                </div>
                <button type="button" wire:click="$set('showDeleteModal', true)" class="btn-danger">
                    Delete organization
                </button>
            </div>

            {{-- Delete confirmation modal --}}
            @if($showDeleteModal)
                <div class="modal-overlay" wire:click.self="$set('showDeleteModal', false)">
                    <div class="modal-box">
                        <div class="modal-header">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <h3 class="modal-title">Confirm deletion</h3>
                        </div>
                        <p class="modal-body">
                            Type <strong class="modal-org-name">{{ $org->name }}</strong> below to confirm you want to permanently delete this organization.
                        </p>
                        <div class="field-group" style="margin-top:16px">
                            <input
                                type="text"
                                wire:model="deleteConfirm"
                                class="field-input-plain {{ $errors->has('deleteConfirm') ? 'has-error' : '' }}"
                                placeholder="Type organization name"
                            >
                            @error('deleteConfirm')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="modal-actions">
                            <button type="button" wire:click="$set('showDeleteModal', false)" class="btn-ghost">Cancel</button>
                            <button type="button" wire:click="deleteOrg" class="btn-danger" wire:loading.attr="disabled">
                                <span wire:loading.remove>Yes, delete permanently</span>
                                <span wire:loading>Deleting…</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @endif

</div>

<style>
.settings-wrap{max-width:640px}
.settings-tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid var(--border);padding-bottom:0}
.tab-btn{display:flex;align-items:center;gap:7px;padding:10px 16px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:'Inter',sans-serif;font-size:13.5px;color:var(--dim);cursor:pointer;transition:all .15s}
.tab-btn:hover{color:var(--text)}
.tab-btn.active{color:var(--text);border-bottom-color:var(--accent)}
.tab-btn.tab-danger.active{color:#f87171;border-bottom-color:#f87171}
.settings-section{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;animation:fadeUp .3s ease both}
.settings-section-header{margin-bottom:24px}
.settings-section-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:#fff;margin:0 0 6px}
.settings-section-sub{font-family:'Inter',sans-serif;font-size:13px;color:var(--dim);margin:0}
.field-group{display:flex;flex-direction:column;gap:6px;margin-bottom:20px}
.field-label{font-family:'Inter',sans-serif;font-size:12.5px;font-weight:500;color:var(--dim);display:flex;align-items:center;gap:6px}
.req{color:var(--accent);font-size:14px}
.logo-section{display:flex;align-items:center;gap:16px}
.current-logo{position:relative}
.logo-preview{width:64px;height:64px;border-radius:12px;object-fit:cover;border:1px solid var(--border2)}
.logo-placeholder{width:64px;height:64px;border-radius:12px;background:rgba(126,232,162,.1);border:1px solid rgba(126,232,162,.2);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--accent)}
.logo-remove-btn{position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);background:var(--danger);color:#fff;border:none;border-radius:4px;font-size:10px;padding:2px 6px;cursor:pointer;white-space:nowrap}
.logo-upload-btn{display:flex;align-items:center;gap:7px;padding:9px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;color:var(--dim);cursor:pointer;transition:all .15s}
.logo-upload-btn:hover{border-color:var(--border2);color:var(--text)}
.field-wrap{background:var(--bg);border:1px solid var(--border);border-radius:9px;transition:border-color .2s,box-shadow .2s}
.field-wrap:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(126,232,162,.1)}
.field-wrap.has-error{border-color:#ef4444}
.field-input-plain{width:100%;background:transparent;border:none;outline:none;padding:12px 14px;font-family:'Inter',sans-serif;font-size:13.5px;color:var(--text);caret-color:var(--accent);border-radius:9px}
.field-input-plain::placeholder{color:var(--muted)}
.field-input-plain.has-error{border:1px solid #ef4444}
.field-readonly{padding:11px 14px;background:var(--bg);border:1px solid var(--border);border-radius:9px;font-family:'DM Mono',monospace;font-size:13px;color:var(--muted)}
.field-error{font-family:'Inter',sans-serif;font-size:11.5px;color:#f87171;margin:0}
.field-hint-inline{display:flex;align-items:center;gap:5px;font-family:'Inter',sans-serif;font-size:11.5px;color:var(--muted);margin:0}
.plan-badge{display:inline-flex;align-items:center;gap:10px;padding:8px 14px;border-radius:8px;font-family:'DM Mono',monospace;font-size:13px;font-weight:500}
.plan-free{background:rgba(91,58,158,.1);color:#c4b5fd;border:1px solid rgba(91,58,158,.2)}
.plan-pro{background:rgba(245,158,11,.1);color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.plan-enterprise{background:rgba(126,232,162,.08);color:var(--accent);border:1px solid rgba(126,232,162,.2)}
.plan-upgrade{font-family:'Inter',sans-serif;font-size:12px;color:var(--accent);text-decoration:none;font-weight:500}
.plan-upgrade:hover{text-decoration:underline}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)}
.btn-ghost{display:flex;align-items:center;gap:6px;padding:9px 16px;background:none;border:1px solid var(--border);border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;color:var(--dim);cursor:pointer;transition:all .15s;text-decoration:none}
.btn-ghost:hover{border-color:var(--border2);color:var(--text)}
.btn-primary{display:flex;align-items:center;gap:7px;padding:9px 18px;background:var(--accent);border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:13.5px;font-weight:700;color:#080c14;cursor:pointer;transition:all .2s}
.btn-primary:hover:not(:disabled){background:var(--accent2)}
.btn-primary:disabled{opacity:.7;cursor:not-allowed}
.btn-primary span{display:flex;align-items:center;gap:7px}
.loading-inner{display:flex;align-items:center;gap:7px}
.spinner{animation:spin 1s linear infinite}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.danger-card{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:20px;background:rgba(239,68,68,.04);border:1px solid rgba(239,68,68,.2);border-radius:12px}
.danger-info{flex:1}
.danger-title{font-family:'Syne',sans-serif;font-size:14.5px;font-weight:600;color:#fff;margin:0 0 6px}
.danger-desc{font-family:'Inter',sans-serif;font-size:13px;color:var(--dim);margin:0;line-height:1.5}
.btn-danger{display:flex;align-items:center;gap:7px;padding:9px 16px;background:var(--danger);border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;color:#fff;cursor:pointer;transition:all .15s;white-space:nowrap}
.btn-danger:hover:not(:disabled){background:#dc2626}
.btn-danger:disabled{opacity:.7;cursor:not-allowed}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px}
.modal-box{width:100%;max-width:440px;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;animation:fadeUp .25s ease both}
.modal-header{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#fff;margin:0}
.modal-body{font-family:'Inter',sans-serif;font-size:13.5px;color:var(--dim);line-height:1.6;margin:0}
.modal-org-name{color:#fff}
.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
</style>
