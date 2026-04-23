<div>
    <x-slot name="header">{{ $project->name }} — Settings</x-slot>

    <div class="max-w-2xl space-y-5">

        {{-- Back link --}}
        <flux:button variant="ghost" size="sm" icon="arrow-left"
            href="{{ route('backend.projectBoard', $project) }}" wire:navigate>
            Back to board
        </flux:button>

        {{-- Tab buttons --}}
        <div class="flex gap-1 border-b" style="border-color: var(--border)">
            <button 
                wire:click="$set('tab', 'general')"
                class="px-4 py-2 text-sm font-medium transition-all"
                :class="tab === 'general' ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--dim)]'">
                General
            </button>
            <button 
                wire:click="$set('tab', 'team')"
                class="px-4 py-2 text-sm font-medium transition-all"
                :class="tab === 'team' ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--dim)]'">
                Team
            </button>
            <button 
                wire:click="$set('tab', 'portal')"
                class="px-4 py-2 text-sm font-medium transition-all"
                :class="tab === 'portal' ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--dim)]'">
                Client Portal
            </button>
            <button 
                wire:click="$set('tab', 'github')"
                class="px-4 py-2 text-sm font-medium transition-all"
                :class="tab === 'github' ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--dim)]'">
                GitHub
            </button>
            <button 
                wire:click="$set('tab', 'danger')"
                class="px-4 py-2 text-sm font-medium transition-all"
                :class="tab === 'danger' ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--dim)]'">
                Danger
            </button>
        </div>

        {{-- ── GENERAL TAB ─────────────────────────────────────────── --}}
        @if($tab === 'general')
            <flux:card class="space-y-5" style="background-color: var(--surface); border-color: var(--border)">
                <flux:heading size="lg">General Settings</flux:heading>

                <form wire:submit="saveGeneral" class="space-y-4">
                    <flux:field>
                        <flux:label>Project name <span class="text-red-400">*</span></flux:label>
                        <flux:input wire:model="name" icon="folder-open"/>
                        <flux:error name="name"/>
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="3"/>
                        <flux:error name="description"/>
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select wire:model="status">
                                <flux:select.option value="planning">Planning</flux:select.option>
                                <flux:select.option value="active">Active</flux:select.option>
                                <flux:select.option value="on_hold">On Hold</flux:select.option>
                                <flux:select.option value="completed">Completed</flux:select.option>
                                <flux:select.option value="cancelled">Cancelled</flux:select.option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Priority</flux:label>
                            <flux:select wire:model="priority">
                                <flux:select.option value="low">Low</flux:select.option>
                                <flux:select.option value="medium">Medium</flux:select.option>
                                <flux:select.option value="high">High</flux:select.option>
                                <flux:select.option value="critical">Critical</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Start date</flux:label>
                            <flux:input type="date" wire:model="start_date"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>Due date</flux:label>
                            <flux:input type="date" wire:model="due_date"/>
                            <flux:error name="due_date"/>
                        </flux:field>
                    </div>

                    <div class="flex justify-end pt-2 border-t" style="border-color: var(--border)">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Save changes</span>
                            <span wire:loading>Saving…</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        {{-- ── TEAM TAB ────────────────────────────────────────────── --}}
        @if($tab === 'team')
            <div class="space-y-4">
                {{-- Add member --}}
                <flux:card style="background-color: var(--surface); border-color: var(--border)">
                    <flux:heading size="sm" class="mb-4">Add team member</flux:heading>
                    <form wire:submit="addMember" class="flex flex-wrap gap-3 items-start">
                        <div class="flex-1 min-w-40">
                            <flux:select wire:model="addMemberId" placeholder="Select org member…">
                                @foreach($orgMembers as $m)
                                    <flux:select.option value="{{ $m->user_id }}">{{ $m->user->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="addMemberId"/>
                        </div>
                        <flux:select wire:model="addMemberRole" class="w-36">
                            <flux:select.option value="manager">Manager</flux:select.option>
                            <flux:select.option value="member">Member</flux:select.option>
                            <flux:select.option value="viewer">Viewer</flux:select.option>
                        </flux:select>
                        <flux:button type="submit" variant="primary" size="sm" icon="user-plus">Add</flux:button>
                    </form>
                </flux:card>

                {{-- Current team --}}
                <flux:card class="!p-0 overflow-hidden" style="background-color: var(--surface); border-color: var(--border)">
                    <div class="px-4 py-3 border-b" style="border-color: var(--border)">
                        <span class="font-mono text-[10px] uppercase tracking-[1.2px]" style="color: var(--muted)">
                            Current team ({{ $projectMembers->count() }})
                        </span>
                    </div>
                    <div class="divide-y" style="border-color: var(--border)">
                        @foreach($projectMembers as $m)
                            <div class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--surface2)]" wire:key="pm-{{ $m->id }}">
                                <flux:avatar src="{{ $m->user->avatar_url }}" name="{{ $m->user->name }}" size="sm"/>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--text)">{{ $m->user->name }}</p>
                                    <p class="text-xs" style="color: var(--muted)">{{ $m->user->email }}</p>
                                </div>
                                <flux:select
                                    wire:change="changeMemberRole({{ $m->user_id }}, $event.target.value)"
                                    class="w-28 text-xs"
                                    size="sm"
                                >
                                    @foreach(['manager' => 'Manager','member' => 'Member','viewer' => 'Viewer'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ $m->role === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </flux:select>
                                @if($m->user_id !== Auth::id())
                                    <flux:button
                                        variant="ghost" size="sm" icon="trash"
                                        wire:click="removeMember({{ $m->user_id }})"
                                        class="hover:text-red-400"
                                        style="color: var(--muted)"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            </div>
        @endif

        {{-- ── CLIENT PORTAL TAB ────────────────────────────────────── --}}
        @if($tab === 'portal')
            <flux:card class="space-y-5" style="background-color: var(--surface); border-color: var(--border)">
                <div>
                    <flux:heading size="lg">Client Portal</flux:heading>
                    <flux:text class="mt-1">Give your client a private link to track project progress in real-time.</flux:text>
                </div>

                <form wire:submit="savePortal" class="space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-xl border" style="background-color: var(--surface2); border-color: var(--border)">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--text)">Enable client portal</p>
                            <p class="text-xs mt-0.5" style="color: var(--muted)">When enabled your client can view progress at the portal URL.</p>
                        </div>
                        <flux:switch wire:model="portal_enabled"/>
                    </div>

                    @if($portal_enabled)
                        <flux:field>
                            <flux:label>Portal URL</flux:label>
                            <div class="flex gap-2">
                                <flux:input
                                    value="{{ route('backend.projectPortal', $project->client_token) }}"
                                    readonly
                                    class="flex-1 font-mono text-xs"
                                    style="color: var(--accent)"
                                />
                                <flux:button
                                    type="button" variant="ghost" icon="clipboard"
                                    x-data
                                    @click="navigator.clipboard.writeText('{{ route('backend.projectPortal', $project->client_token) }}'); $dispatch('toast', { message: 'Link copied!', type: 'success' })"
                                />
                            </div>
                        </flux:field>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Client name</flux:label>
                                <flux:input wire:model="client_name" placeholder="Acme Corp" icon="building-office"/>
                                <flux:error name="client_name"/>
                            </flux:field>
                            <flux:field>
                                <flux:label>Client email</flux:label>
                                <flux:input wire:model="client_email" type="email" placeholder="client@acme.com" icon="envelope"/>
                                <flux:error name="client_email"/>
                            </flux:field>
                        </div>
                    @endif

                    <div class="flex justify-end pt-2 border-t" style="border-color: var(--border)">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Save portal settings</span>
                            <span wire:loading>Saving…</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        {{-- ── GITHUB TAB ───────────────────────────────────────────── --}}
        @if($tab === 'github')
            <flux:card class="space-y-5" style="background-color: var(--surface); border-color: var(--border)">
                <div>
                    <flux:heading size="lg">GitHub Integration</flux:heading>
                    <flux:text class="mt-1">Link a GitHub repository. Pull request merges will automatically complete tasks.</flux:text>
                </div>

                <form wire:submit="saveGithub" class="space-y-4">
                    <flux:field>
                        <flux:label>
                            Repository
                            <flux:description>Format: owner/repo-name</flux:description>
                        </flux:label>
                        <flux:input wire:model="github_repo" placeholder="acme/website" icon="code-bracket"/>
                        <flux:error name="github_repo"/>
                    </flux:field>

                    <flux:field>
                        <flux:label>Default branch</flux:label>
                        <flux:input wire:model="github_branch" placeholder="main"/>
                        <flux:error name="github_branch"/>
                    </flux:field>

                    <div class="flex justify-end pt-2 border-t" style="border-color: var(--border)">
                        <flux:button type="submit" variant="primary">Save GitHub settings</flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        {{-- ── DANGER TAB ─────────────────────────────────────── --}}
        @if($tab === 'danger')
            <flux:card class="space-y-5" style="background-color: var(--surface); border-color: rgba(239, 68, 68, 0.2)">
                <div>
                    <flux:heading size="lg" class="text-red-400">Danger Zone</flux:heading>
                    <flux:text class="mt-1">Permanent actions. Please read carefully.</flux:text>
                </div>

                <div class="flex items-start justify-between gap-4 p-4 rounded-xl border" style="background-color: var(--surface2); border-color: var(--border)">
                    <div>
                        <p class="text-sm font-semibold font-['Syne']" style="color: var(--text)">Archive project</p>
                        <p class="text-sm mt-1 max-w-sm" style="color: var(--dim)">
                            Moves this project to the archive. No data is deleted. You can unarchive at any time.
                        </p>
                    </div>
                    <flux:modal.trigger name="archive-project">
                        <flux:button variant="ghost" size="sm" class="border-amber-500/30 text-amber-400 hover:border-amber-400 flex-shrink-0">
                            Archive
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </flux:card>
        @endif

    </div>

    {{-- Archive modal --}}
    <flux:modal name="archive-project" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading>Archive project?</flux:heading>
            <flux:text>
                Type <strong class="text-white">{{ $project->name }}</strong> to confirm you want to archive this project.
            </flux:text>
            <flux:field>
                <flux:input wire:model="archiveConfirm" placeholder="Type project name"/>
                <flux:error name="archiveConfirm"/>
            </flux:field>
            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button
                    class="bg-amber-500 hover:bg-amber-400 text-black font-semibold"
                    wire:click="archiveProject"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Archive project</span>
                    <span wire:loading>Archiving…</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>