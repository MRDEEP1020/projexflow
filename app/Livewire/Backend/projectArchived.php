<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Layout('components.layouts.app')]
#[Title('Archive')]
class ProjectArchived extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'archived_at';
    public string $direction = 'desc';
    public ?int $unarchiving = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $col): void
    {
        $this->sortBy = $col;
        $this->direction = ($this->sortBy === $col && $this->direction === 'desc') ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function unarchive(int $projectId): void
    {
        $project = Project::where('id', $projectId)
            ->where('org_id', Session::get('active_org_id'))
            ->whereNotNull('archived_at')
            ->firstOrFail();

        // Must be project manager or org admin
        $role = $project->projectMembers()->where('user_id', Auth::id())->value('role');
        abort_unless(in_array($role, ['manager', 'owner']), 403);

        $project->update(['archived_at' => null]);
        $this->unarchiving = null;
        $this->dispatch('toast', ['message' => "\"{$project->name}\" restored to active projects.", 'type' => 'success']);
    }

    public function getProjectsProperty()
    {
        $orgId = Session::get('active_org_id');
        $user = Auth::user();

        return Project::query()
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNotNull('archived_at')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy($this->sortBy, $this->direction)
            ->paginate(15);
    }

    public function getTotalArchivedProperty()
    {
        $orgId = Session::get('active_org_id');
        $user = Auth::user();

        return Project::query()
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNotNull('archived_at')
            ->count();
    }

    public function render()
    {
        return view('livewire.backend.projectArchived', [
            'projects' => $this->projects,
            'totalArchived' => $this->total_archived,
        ]);
    }
}