<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Layout('components.layouts.app')]
class projectSettings extends Component
{
    public Project $project;
    public string $tab = 'general';

    // General
    public string $name = '';
    public string $description = '';
    public string $status = '';
    public string $priority = '';
    public ?string $start_date = null;
    public ?string $due_date = null;

    // GitHub
    public string $github_repo = '';
    public string $github_branch = 'main';

    // Client portal
    public bool $portal_enabled = false;
    public string $client_name = '';
    public string $client_email = '';

    // Add member
    public int|string $addMemberId = '';
    public string $addMemberRole = 'member';

    // Danger
    public string $archiveConfirm = '';
    public string $deleteConfirm = '';

    public function mount(Project $project): void
    {
        // ── FIXED AUTHORIZATION ──────────────────────────────────────
        // Check 1: Is the user a project manager or higher in project_members?
        $projectRole = $project->projectMembers()
            ->where('user_id', Auth::id())
            ->value('role');

        // Check 2: Is the user an org admin/owner (they should access all projects in org)
        $orgId   = Session::get('active_org_id');
        $orgRole = null;
        if ($orgId) {
            $orgRole = OrganizationMember::where('org_id', $orgId)
                ->where('user_id', Auth::id())
                ->value('role');
        }

        $canAccess =
            in_array($projectRole, ['manager', 'owner'])   // project manager/owner
            || in_array($orgRole, ['owner', 'admin']);       // OR org admin/owner

        abort_unless($canAccess, 403, 'You do not have permission to manage this project.');

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

    // ── Helper: re-check auth before any mutation ────────────────────
    protected function authorizeManage(): void
    {
        $projectRole = $this->project->projectMembers()
            ->where('user_id', Auth::id())
            ->value('role');

        $orgId   = Session::get('active_org_id');
        $orgRole = $orgId
            ? OrganizationMember::where('org_id', $orgId)->where('user_id', Auth::id())->value('role')
            : null;

        abort_unless(
            in_array($projectRole, ['manager', 'owner']) || in_array($orgRole, ['owner', 'admin']),
            403
        );
    }

    // ── General ─────────────────────────────────────────────
    public function saveGeneral(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:planning,active,on_hold,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $this->project->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'priority' => $this->priority,
            'start_date' => $this->start_date ?: null,
            'due_date' => $this->due_date ?: null,
        ]);

        $this->dispatch('toast', ['message' => 'Project settings saved.', 'type' => 'success']);
    }

    // ── GitHub ───────────────────────────────────────────────
    public function saveGithub(): void
    {
        $this->validate([
            'github_repo' => ['nullable', 'string', 'regex:/^[\w.\-]+\/[\w.\-]+$/', 'max:255'],
            'github_branch' => ['required', 'string', 'max:100'],
        ]);

        $this->project->update([
            'github_repo' => $this->github_repo ?: null,
            'github_branch' => $this->github_branch,
        ]);

        $this->dispatch('toast', ['message' => 'GitHub settings saved.', 'type' => 'success']);
    }

    // ── Client portal ────────────────────────────────────────
    public function savePortal(): void
    {
        $this->validate([
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_email' => ['nullable', 'email', 'max:191'],
        ]);

        $this->project->update([
            'client_portal_enabled' => $this->portal_enabled,
            'client_name' => $this->client_name ?: null,
            'client_email' => $this->client_email ?: null,
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
            'addMemberId' => ['required', 'integer', 'exists:users,id'],
            'addMemberRole' => ['required', 'in:manager,member,viewer'],
        ]);

        $already = $this->project->projectMembers()->where('user_id', $this->addMemberId)->exists();
        if ($already) {
            $this->addError('addMemberId', 'This user is already on the project.');
            return;
        }

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->addMemberId,
            'role' => $this->addMemberRole,
        ]);

        $this->addMemberId = '';
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
        $this->redirect(route('backend.projectArchived'), navigate: true);
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
        if (!$orgId) return collect();

        $existing = $this->project->projectMembers()->pluck('user_id');

        return OrganizationMember::where('org_id', $orgId)
            ->whereNotIn('user_id', $existing)
            ->with('user')
            ->get();
    }

    public function render()
    {
        return view('livewire.backend.projectSettings', [
            'projectMembers' => $this->projectMembers,
            'orgMembers' => $this->orgMembers,
        ]);
    }
}
