<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

new #[Layout('layouts.app')] class extends Component
{
    public Project $project;
    public string  $tab = 'general';

    // General
    public string  $name        = '';
    public string  $description = '';
    public string  $status      = '';
    public string  $priority    = '';
    public ?string $start_date  = null;
    public ?string $due_date    = null;

    // GitHub
    public string $github_repo   = '';
    public string $github_branch = 'main';

    // Client portal
    public bool   $portal_enabled = false;
    public string $client_name    = '';
    public string $client_email   = '';

    // Add member
    public int|string $addMemberId   = '';
    public string     $addMemberRole = 'member';

    // Danger
    public string $archiveConfirm = '';
    public string $deleteConfirm  = '';

    public function mount(Project $project): void
    {
        // Must be project manager or org admin/owner
        $role = $project->projectMembers()->where('user_id', Auth::id())->value('role');
        abort_unless(in_array($role, ['manager','owner']), 403);

        $this->project       = $project;
        $this->name          = $project->name;
        $this->description   = $project->description ?? '';
        $this->status        = $project->status;
        $this->priority      = $project->priority;
        $this->start_date    = $project->start_date?->format('Y-m-d');
        $this->due_date      = $project->due_date?->format('Y-m-d');
        $this->github_repo   = $project->github_repo ?? '';
        $this->github_branch = $project->github_branch ?? 'main';
        $this->portal_enabled = (bool) $project->client_portal_enabled;
        $this->client_name   = $project->client_name  ?? '';
        $this->client_email  = $project->client_email ?? '';
    }

    // ── General ─────────────────────────────────────────────
    public function saveGeneral(): void
    {
        $this->validate([
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string','max:5000'],
            'status'      => ['required','in:planning,active,on_hold,completed,cancelled'],
            'priority'    => ['required','in:low,medium,high,critical'],
            'start_date'  => ['nullable','date'],
            'due_date'    => ['nullable','date','after_or_equal:start_date'],
        ]);

        $this->project->update([
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'start_date'  => $this->start_date ?: null,
            'due_date'    => $this->due_date    ?: null,
        ]);

        $this->dispatch('toast', ['message' => 'Project settings saved.', 'type' => 'success']);
    }

    // ── GitHub ───────────────────────────────────────────────
    public function saveGithub(): void
    {
        $this->validate([
            'github_repo'   => ['nullable','string','regex:/^[\w.\-]+\/[\w.\-]+$/','max:255'],
            'github_branch' => ['required','string','max:100'],
        ]);

        $this->project->update([
            'github_repo'   => $this->github_repo   ?: null,
            'github_branch' => $this->github_branch,
        ]);

        $this->dispatch('toast', ['message' => 'GitHub settings saved.', 'type' => 'success']);
    }

    // ── Client portal ────────────────────────────────────────
    public function savePortal(): void
    {
        $this->validate([
            'client_name'  => ['nullable','string','max:150'],
            'client_email' => ['nullable','email','max:191'],
        ]);

        $this->project->update([
            'client_portal_enabled' => $this->portal_enabled,
            'client_name'           => $this->client_name  ?: null,
            'client_email'          => $this->client_email ?: null,
        ]);

        $this->dispatch('toast', ['message' => 'Portal settings saved.', 'type' => 'success']);
    }

    public function copyPortalLink(): void
    {
        $this->dispatch('copy-to-clipboard', ['text' => route('portal.show', $this->project->client_token)]);
    }

    // ── Team ─────────────────────────────────────────────────
    public function addMember(): void
    {
        $this->validate([
            'addMemberId'   => ['required','integer','exists:users,id'],
            'addMemberRole' => ['required','in:manager,member,viewer'],
        ]);

        $already = $this->project->projectMembers()->where('user_id', $this->addMemberId)->exists();
        if ($already) {
            $this->addError('addMemberId', 'This user is already on the project.');
            return;
        }

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id'    => $this->addMemberId,
            'role'       => $this->addMemberRole,
        ]);

        $this->addMemberId   = '';
        $this->addMemberRole = 'member';
        $this->dispatch('toast', ['message' => 'Member added.', 'type' => 'success']);
    }

    public function removeMember(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->dispatch('toast', ['message' => 'You cannot remove yourself.', 'type' => 'error']);
            return;
        }
        ProjectMember::where('project_id', $this->project->id)
                     ->where('user_id', $userId)
                     ->delete();
        $this->dispatch('toast', ['message' => 'Member removed.', 'type' => 'info']);
    }

    public function changeMemberRole(int $userId, string $role): void
    {
        ProjectMember::where('project_id', $this->project->id)
                     ->where('user_id', $userId)
                     ->update(['role' => $role]);
        $this->dispatch('toast', ['message' => 'Role updated.', 'type' => 'success']);
    }

    // ── Danger ───────────────────────────────────────────────
    public function archiveProject(): void
    {
        if ($this->archiveConfirm !== $this->project->name) {
            $this->addError('archiveConfirm', 'Project name does not match.');
            return;
        }
        $this->project->update(['archived_at' => now()]);
        $this->dispatch('toast', ['message' => 'Project archived.', 'type' => 'info']);
        $this->redirect(route('archive'), navigate: true);
    }

    // ── Computed ─────────────────────────────────────────────
    #[Computed]
    public function projectMembers()
    {
        return $this->project->projectMembers()->with('user')->get();
    }

    #[Computed]
    public function orgMembers()
    {
        $orgId = Session::get('active_org_id');
        if (! $orgId) return collect();

        $existing = $this->project->projectMembers()->pluck('user_id');

        return OrganizationMember::where('org_id', $orgId)
                                 ->whereNotIn('user_id', $existing)
                                 ->with('user')
                                 ->get();
    }
}; ?>

<x-slot name="header">{{ $project->name }} — Settings</x-slot>

<div class="max-w-2xl space-y-5">

    {{-- Back link --}}
    <flux:button variant="ghost" size="sm" icon="arrow-left"
        href="{{ route('projects.show', $project) }}" wire:navigate>
        Back to board
    </flux:button>

    {{-- Tabs --}}
    <flux:tabs wire:model="tab">
        <flux:tab name="general"  icon="pencil-square">General</flux:tab>
        <flux:tab name="team"     icon="user-group">Team</flux:tab>
        <flux:tab name="portal"   icon="globe-alt">Client Portal</flux:tab>
        <flux:tab name="github"   icon="code-bracket">GitHub</flux:tab>
        <flux:tab name="danger"   icon="exclamation-triangle">Danger</flux:tab>
    </flux:tabs>

    {{-- ── GENERAL ─────────────────────────────────────────── --}}
    @if($tab === 'general')
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-5" wire:key="general">
            <flux:heading size="lg">General Settings</flux:heading>

            <form wire:submit="saveGeneral" class="space-y-4">
                <flux:field>
                    <flux:label>Project name <flux:required/></flux:label>
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
                            <flux:option value="planning">Planning</flux:option>
                            <flux:option value="active">Active</flux:option>
                            <flux:option value="on_hold">On Hold</flux:option>
                            <flux:option value="completed">Completed</flux:option>
                            <flux:option value="cancelled">Cancelled</flux:option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Priority</flux:label>
                        <flux:select wire:model="priority">
                            <flux:option value="low">Low</flux:option>
                            <flux:option value="medium">Medium</flux:option>
                            <flux:option value="high">High</flux:option>
                            <flux:option value="critical">Critical</flux:option>
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

                <div class="flex justify-end pt-2 border-t border-[#1c2e45]">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save changes</span>
                        <span wire:loading>Saving…</span>
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- ── TEAM ────────────────────────────────────────────── --}}
    @if($tab === 'team')
        <div class="space-y-4" wire:key="team">

            {{-- Add member --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45]">
                <flux:heading size="sm" class="mb-4">Add team member</flux:heading>
                <form wire:submit="addMember" class="flex flex-wrap gap-3 items-start">
                    <div class="flex-1 min-w-40">
                        <flux:select wire:model="addMemberId" placeholder="Select org member…">
                            @foreach($this->orgMembers as $m)
                                <flux:option value="{{ $m->user_id }}">{{ $m->user->name }}</flux:option>
                            @endforeach
                        </flux:select>
                        <flux:error name="addMemberId"/>
                    </div>
                    <flux:select wire:model="addMemberRole" class="w-36">
                        <flux:option value="manager">Manager</flux:option>
                        <flux:option value="member" selected>Member</flux:option>
                        <flux:option value="viewer">Viewer</flux:option>
                    </flux:select>
                    <flux:button type="submit" variant="primary" size="sm" icon="user-plus">Add</flux:button>
                </form>
            </flux:card>

            {{-- Current team --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
                <div class="px-4 py-3 border-b border-[#1c2e45]">
                    <span class="font-mono text-[10px] uppercase tracking-[1.2px] text-[#506070]">
                        Current team ({{ $this->projectMembers->count() }})
                    </span>
                </div>
                <div class="divide-y divide-[#1c2e45]">
                    @foreach($this->projectMembers as $m)
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-[#131d2e]" wire:key="pm-{{ $m->id }}">
                            <flux:avatar src="{{ $m->user->avatar_url }}" name="{{ $m->user->name }}" size="sm"/>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#dde6f0] truncate">{{ $m->user->name }}</p>
                                <p class="text-xs text-[#506070]">{{ $m->user->email }}</p>
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
                                    class="text-[#506070] hover:text-red-400"
                                />
                            @endif
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    @endif

    {{-- ── CLIENT PORTAL ────────────────────────────────────── --}}
    @if($tab === 'portal')
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-5" wire:key="portal">
            <div>
                <flux:heading size="lg">Client Portal</flux:heading>
                <flux:text class="mt-1">Give your client a private link to track project progress in real-time.</flux:text>
            </div>

            <form wire:submit="savePortal" class="space-y-4">

                {{-- Enable toggle --}}
                <div class="flex items-center justify-between p-4 rounded-xl border border-[#1c2e45] bg-[#080c14]">
                    <div>
                        <p class="text-sm font-medium text-[#dde6f0]">Enable client portal</p>
                        <p class="text-xs text-[#506070] mt-0.5">When enabled your client can view progress at the portal URL.</p>
                    </div>
                    <flux:switch wire:model="portal_enabled"/>
                </div>

                @if($portal_enabled)
                    {{-- Portal URL --}}
                    <flux:field>
                        <flux:label>Portal URL</flux:label>
                        <div class="flex gap-2">
                            <flux:input
                                value="{{ route('portal.show', $project->client_token) }}"
                                readonly
                                class="flex-1 font-mono text-xs text-[#7EE8A2]"
                            />
                            <flux:button
                                type="button" variant="ghost" icon="clipboard"
                                x-data
                                @click="navigator.clipboard.writeText('{{ route('portal.show', $project->client_token) }}'); $dispatch('toast', { message: 'Link copied!', type: 'success' })"
                            />
                        </div>
                    </flux:field>

                    {{-- Client details --}}
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

                <div class="flex justify-end pt-2 border-t border-[#1c2e45]">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save portal settings</span>
                        <span wire:loading>Saving…</span>
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- ── GITHUB ───────────────────────────────────────────── --}}
    @if($tab === 'github')
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-5" wire:key="github">
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

                <div class="flex justify-end pt-2 border-t border-[#1c2e45]">
                    <flux:button type="submit" variant="primary">Save GitHub settings</flux:button>
                </div>
            </form>

            {{-- Webhook setup guide --}}
            @if($project->github_repo)
                <flux:callout icon="information-circle" color="blue">
                    <flux:callout.heading>Webhook setup</flux:callout.heading>
                    <flux:callout.text>
                        In your GitHub repo go to <strong>Settings → Webhooks → Add webhook</strong>.
                        Set the Payload URL to:
                    </flux:callout.text>
                    <div class="mt-2 flex gap-2 items-center">
                        <code class="flex-1 text-xs bg-[#080c14] border border-[#1c2e45] rounded-lg px-3 py-2 font-mono text-[#7EE8A2] break-all">
                            {{ config('app.url') }}/webhooks/github
                        </code>
                        <flux:button
                            type="button" variant="ghost" size="sm" icon="clipboard"
                            x-data
                            @click="navigator.clipboard.writeText('{{ config('app.url') }}/webhooks/github')"
                        />
                    </div>
                    <flux:callout.text class="mt-2">
                        Set content type to <code class="text-xs bg-[#080c14] px-1 rounded">application/json</code>,
                        add your secret from <code class="text-xs bg-[#080c14] px-1 rounded">GITHUB_WEBHOOK_SECRET</code> env,
                        and select <strong>Pull requests</strong> and <strong>Pushes</strong> events.
                    </flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    @endif

    {{-- ── DANGER ZONE ─────────────────────────────────────── --}}
    @if($tab === 'danger')
        <flux:card class="bg-[#0e1420] border-red-500/20 space-y-5" wire:key="danger">
            <div>
                <flux:heading size="lg" class="text-red-400">Danger Zone</flux:heading>
                <flux:text class="mt-1">Permanent actions. Please read carefully.</flux:text>
            </div>

            {{-- Archive --}}
            <div class="flex items-start justify-between gap-4 p-4 rounded-xl border border-[#1c2e45] bg-[#080c14]">
                <div>
                    <p class="text-sm font-semibold text-white font-['Syne']">Archive project</p>
                    <p class="text-sm text-[#8da0b8] mt-1 max-w-sm">
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
