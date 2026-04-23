<?php

namespace App\Livewire\Backend;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public ?Organization $org = null;

    // Stats
    public int $activeProjectsCount  = 0;
    public int $tasksDueTodayCount   = 0;
    public int $teamMembersCount     = 0;
    public int $overdueTasksCount    = 0;

    // Data
    public $recentProjects;
    public $tasksDueToday;

    protected function getListeners(): array
    {
        $orgId = $this->org?->id;
        return $orgId ? [
            "echo-private:org.{$orgId},.project.created" => '$refresh',
            "echo-private:org.{$orgId},.project.updated" => '$refresh',
        ] : [];
    }

    public function mount(): void
    {
        $orgId    = Session::get('active_org_id');
        $this->org = $orgId ? Organization::find($orgId) : null;

        $this->loadData();
    }

    protected function loadData(): void
    {
        $user  = Auth::user();
        $orgId = $this->org?->id;

        // Active projects (org-scoped)
        $projectQuery = Project::query()
            ->whereNull('archived_at')
            ->whereNotIn('status', ['cancelled']);

        if ($orgId) {
            $projectQuery->where('org_id', $orgId);
        } else {
            $projectQuery->whereNull('org_id')
                         ->where('created_by', $user->id);
        }

        $this->activeProjectsCount = $projectQuery->count();

        $this->recentProjects = (clone $projectQuery)
            ->latest('updated_at')
            ->limit(8)
            ->get();

        // Tasks due today (user-scoped, cross-project)
        $this->tasksDueTodayCount = Task::where('assigned_to', $user->id)
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['done'])
            ->count();

        $this->tasksDueToday = Task::where('assigned_to', $user->id)
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['done'])
            ->with(['project'])
            ->limit(5)
            ->get();

        // Team members (org-scoped)
        $this->teamMembersCount = $orgId
            ? \App\Models\OrganizationMember::where('org_id', $orgId)->count()
            : 1;

        // Overdue tasks (user-scoped)
        $this->overdueTasksCount = Task::where('assigned_to', $user->id)
            ->whereDate('due_date', '<', today())
            ->whereNotIn('status', ['done'])
            ->count();
    }

    public function render()
    {
        return view('livewire.backend.dashboard');
    }
}
