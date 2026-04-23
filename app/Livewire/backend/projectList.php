<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Layout('layouts.app')]
#[Title('Projects')]
class projectList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $sortBy = 'updated_at';
    public string $direction = 'desc';

    protected $listeners = [];

    public function mount(): void
    {
        $orgId = Session::get('active_org_id');
        
        if ($orgId) {
            $this->listeners = [
                "echo-private:org.{$orgId},.project.created" => '$refresh',
            ];
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function sort(string $col): void
    {
        if ($this->sortBy === $col) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $col;
            $this->direction = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function projects()
    {
        $orgId = Session::get('active_org_id');
        $user = Auth::user();

        return Project::query()
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNull('archived_at')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->orderBy($this->sortBy, $this->direction)
            ->paginate(12);
    }

    #[Computed]
    public function counts(): array
    {
        $orgId = Session::get('active_org_id');
        $user = Auth::user();

        $base = Project::query()
            ->when($orgId, fn($q) => $q->where('org_id', $orgId))
            ->when(!$orgId, fn($q) => $q->whereNull('org_id')->where('created_by', $user->id))
            ->whereNull('archived_at');

        return [
            'all' => (clone $base)->count(),
            'planning' => (clone $base)->where('status', 'planning')->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'on_hold' => (clone $base)->where('status', 'on_hold')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.backend.projectList', [
            'projects' => $this->projects,
            'counts' => $this->counts,
        ]);
    }
}