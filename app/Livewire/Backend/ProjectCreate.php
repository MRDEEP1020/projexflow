<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('New Project')]
class ProjectCreate extends Component
{
    public string $name = '';
    public string $description = '';
    public string $status = 'planning';
    public string $priority = 'medium';
    public ?string $start_date = null;
    public ?string $due_date = null;
    public string $github_repo = '';
    public string $client_name = '';
    public string $client_email = '';
    public bool $showClient = false;

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:planning,active,on_hold,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'github_repo' => ['nullable', 'string', 'regex:/^[\w.\-]+\/[\w.\-]+$/', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_email' => ['nullable', 'email', 'max:191'],
        ]);

        $orgId = Session::get('active_org_id');

        DB::transaction(function () use ($orgId) {
            $project = Project::create([
                'name' => $this->name,
                'description' => $this->description ?: null,
                'org_id' => $orgId,
                'created_by' => Auth::id(),
                'status' => $this->status,
                'priority' => $this->priority,
                'start_date' => $this->start_date ?: null,
                'due_date' => $this->due_date ?: null,
                'github_repo' => $this->github_repo ?: null,
                'client_name' => $this->client_name ?: null,
                'client_email' => $this->client_email ?: null,
                'client_token' => Str::random(64),
                'client_portal_enabled' => false,
                'progress_percentage' => 0,
            ]);

            // Creator becomes project manager automatically
            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'role' => 'manager',
            ]);

            // Broadcast project created to org channel
            // broadcast(new ProjectCreated($project))->toOthers();

            $this->dispatch('toast', ['message' => "Project \"{$project->name}\" created!", 'type' => 'success']);
            $this->redirect(route('backend.projectBoard', $project), navigate: true);
        });
    }

    public function render()
    {
        return view('livewire.backend.projectCreate');
    }
}