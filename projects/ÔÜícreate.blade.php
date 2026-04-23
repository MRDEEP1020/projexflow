<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

new #[Layout('layouts.app')] #[Title('New Project')] class extends Component
{
    public string  $name          = '';
    public string  $description   = '';
    public string  $status        = 'planning';
    public string  $priority      = 'medium';
    public ?string $start_date    = null;
    public ?string $due_date      = null;
    public string  $github_repo   = '';
    public string  $client_name   = '';
    public string  $client_email  = '';
    public bool    $showClient    = false;

    public function save(): void
    {
        $this->validate([
            'name'         => ['required','string','max:255'],
            'description'  => ['nullable','string','max:5000'],
            'status'       => ['required','in:planning,active,on_hold,completed,cancelled'],
            'priority'     => ['required','in:low,medium,high,critical'],
            'start_date'   => ['nullable','date'],
            'due_date'     => ['nullable','date','after_or_equal:start_date'],
            'github_repo'  => ['nullable','string','regex:/^[\w.\-]+\/[\w.\-]+$/','max:255'],
            'client_name'  => ['nullable','string','max:150'],
            'client_email' => ['nullable','email','max:191'],
        ]);

        $orgId = Session::get('active_org_id');

        DB::transaction(function () use ($orgId) {
            $project = Project::create([
                'name'                 => $this->name,
                'description'          => $this->description ?: null,
                'org_id'               => $orgId,
                'created_by'           => Auth::id(),
                'status'               => $this->status,
                'priority'             => $this->priority,
                'start_date'           => $this->start_date ?: null,
                'due_date'             => $this->due_date   ?: null,
                'github_repo'          => $this->github_repo ?: null,
                'client_name'          => $this->client_name  ?: null,
                'client_email'         => $this->client_email ?: null,
                'client_token'         => Str::random(64),
                'client_portal_enabled'=> false,
                'progress_percentage'  => 0,
            ]);

            // Creator becomes project manager automatically
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id'    => Auth::id(),
                'role'       => 'manager',
            ]);

            // Broadcast project created to org channel
            // broadcast(new ProjectCreated($project))->toOthers();

            $this->dispatch('toast', ['message' => "Project \"{$project->name}\" created!", 'type' => 'success']);
            $this->redirect(route('projects.show', $project), navigate: true);
        });
    }
}; ?>

<x-slot name="header">New Project</x-slot>

<div class="max-w-2xl">

    <flux:card class="bg-[#0e1420] border-[#1c2e45]">

        {{-- Card header --}}
        <div class="mb-6">
            <div class="eyebrow mb-2">
                <span class="eyebrow-dot"></span>
                New workspace project
            </div>
            <flux:heading size="xl">Create a project</flux:heading>
            <flux:text class="mt-1">Set up a new project and invite your team.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-5">

            {{-- Name --}}
            <flux:field>
                <flux:label>Project name <flux:required/></flux:label>
                <flux:input
                    wire:model="name"
                    placeholder="e.g. Website Redesign v2"
                    icon="folder-open"
                    autofocus
                />
                <flux:error name="name"/>
            </flux:field>

            {{-- Description --}}
            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea
                    wire:model="description"
                    placeholder="What is this project about? What's the goal?"
                    rows="3"
                />
                <flux:error name="description"/>
            </flux:field>

            {{-- Status + Priority --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Status <flux:required/></flux:label>
                    <flux:select wire:model="status">
                        <flux:option value="planning">Planning</flux:option>
                        <flux:option value="active">Active</flux:option>
                        <flux:option value="on_hold">On Hold</flux:option>
                    </flux:select>
                    <flux:error name="status"/>
                </flux:field>

                <flux:field>
                    <flux:label>Priority <flux:required/></flux:label>
                    <flux:select wire:model="priority">
                        <flux:option value="low">Low</flux:option>
                        <flux:option value="medium">Medium</flux:option>
                        <flux:option value="high">High</flux:option>
                        <flux:option value="critical">Critical</flux:option>
                    </flux:select>
                    <flux:error name="priority"/>
                </flux:field>
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Start date</flux:label>
                    <flux:input type="date" wire:model="start_date"/>
                    <flux:error name="start_date"/>
                </flux:field>

                <flux:field>
                    <flux:label>Due date</flux:label>
                    <flux:input type="date" wire:model="due_date"/>
                    <flux:error name="due_date"/>
                </flux:field>
            </div>

            {{-- GitHub --}}
            <flux:field>
                <flux:label>
                    GitHub repository
                    <flux:description>Format: owner/repo — e.g. acme/website</flux:description>
                </flux:label>
                <flux:input
                    wire:model="github_repo"
                    placeholder="owner/repository-name"
                    icon="code-bracket"
                />
                <flux:error name="github_repo"/>
            </flux:field>

            {{-- Client details (collapsible) --}}
            <div class="border border-[#1c2e45] rounded-xl overflow-hidden">
                <button
                    type="button"
                    wire:click="$toggle('showClient')"
                    class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-[#131d2e] transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <flux:icon.user-circle class="size-4 text-[#506070]"/>
                        <span class="text-sm font-medium text-[#8da0b8]">Client details</span>
                        <flux:badge size="sm" color="zinc">Optional</flux:badge>
                    </div>
                    <flux:icon.chevron-down
                        class="size-4 text-[#506070] transition-transform {{ $showClient ? 'rotate-180' : '' }}"
                    />
                </button>

                @if($showClient)
                    <div class="px-4 pb-4 space-y-4 border-t border-[#1c2e45] pt-4" wire:key="client-section">
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
                        <flux:callout icon="information-circle" color="blue">
                            <flux:callout.heading>Client portal</flux:callout.heading>
                            <flux:callout.text>
                                Once the project is created you can enable the client portal in Settings → Client Portal. Your client gets a private link to track progress in real-time.
                            </flux:callout.text>
                        </flux:callout>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#1c2e45]">
                <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" icon="folder-plus">
                    <span wire:loading.remove>Create project</span>
                    <span wire:loading>Creating…</span>
                </flux:button>
            </div>

        </form>
    </flux:card>
</div>
