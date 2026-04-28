<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Project;
use App\Models\ClientFeedback;
use App\Models\ClientPortalSession;
use App\Models\Notification;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

#[Layout('components.layouts.app')]
class projectPortal extends Component
{
    // Token and project ID are protected from external manipulation
    public string $token     = '';
    public int    $projectId = 0;

    // Feedback form state
    public string  $feedbackType      = 'comment';
    public string  $feedbackBody      = '';
    public string  $feedbackName      = '';
    public string  $feedbackEmail     = '';
    public ?int    $feedbackMilestone = null;
    public bool    $showFeedbackForm  = false;
    public bool    $feedbackSent      = false;

    protected function getListeners(): array
    {
        return [
            "echo-private:client.{$this->token},.progress.updated" => 'handleProgressUpdate',
        ];
    }

    public function mount(string $token): void
    {
        // ALG-CP-01 Step 1: Validate token format (64 alphanumeric)
        if (! preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
            abort(404);
        }

        // ALG-CP-01 Step 2: Look up project by token
        $project = Project::where('client_token', $token)->first();
        if (! $project) {
            abort(404);
        }

        // ALG-CP-01 Step 3: Check portal is enabled
        if (! $project->client_portal_enabled) {
            abort(403, 'This client portal is not currently available.');
        }

        $this->token     = $token;
        $this->projectId = $project->id;

        // ALG-CP-01 Step 4: Record visit
        ClientPortalSession::updateOrCreate(
            ['project_id' => $project->id, 'ip_address' => Request::ip()],
            ['last_seen_at' => now()]
        );
    }

    // Real-time progress update from Echo broadcast
    public function handleProgressUpdate(array $data): void
    {
        // Force Livewire to re-compute project property
        unset($this->computedPropertyCache['project']);
    }

    // ALG-CP-02: Submit feedback
    public function submitFeedback(): void
    {
        $project = Project::where('client_token', $this->token)
            ->where('client_portal_enabled', true)
            ->firstOrFail();

        $this->validate([
            'feedbackType'      => ['required', 'in:comment,approval,revision_request'],
            'feedbackBody'      => ['required', 'string', 'min:5', 'max:3000'],
            'feedbackName'      => ['nullable', 'string', 'max:100'],
            'feedbackEmail'     => ['nullable', 'email', 'max:191'],
            'feedbackMilestone' => ['nullable', 'integer'],
        ]);

        ClientFeedback::create([
            'project_id'   => $project->id,
            'milestone_id' => $this->feedbackMilestone,
            'type'         => $this->feedbackType,
            'body'         => $this->feedbackBody,
            'client_name'  => $this->feedbackName  ?: $project->client_name,
            'client_email' => $this->feedbackEmail ?: $project->client_email,
        ]);

        // Notify project manager
        $pm = $project->projectMembers()
            ->where('role', 'manager')
            ->first();

        if ($pm) {
            Notification::create([
                'user_id' => $pm->user_id,
                'type'    => 'client_feedback',
                'title'   => 'Client left ' . $this->feedbackType . ' on ' . $project->name,
                'body'    => substr($this->feedbackBody, 0, 120),
                'url'     => route('backend.projectBoard', $project->id),
            ]);
        }

        $this->feedbackSent      = true;
        $this->showFeedbackForm  = false;
        $this->feedbackBody      = '';
        $this->feedbackMilestone = null;
        $this->dispatch('toast', ['message' => 'Your feedback has been sent!', 'type' => 'success']);
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function project(): Project
    {
        return Project::where('client_token', $this->token)
            ->with([
                'org',  // ← Add this to eager-load the organization
                'milestones' => fn($q) => $q->orderBy('due_date')
            ])
            ->firstOrFail();
    }

    #[Computed]
    public function doneTasks()
    {
        return Task::where('project_id', $this->projectId)
            ->where('status', 'done')
            ->whereNotNull('deliverable_url')
            ->whereNull('parent_task_id')
            ->select('id', 'title', 'deliverable_type', 'deliverable_url', 'deliverable_note', 'completed_at', 'milestone_id')
            ->orderByDesc('completed_at')
            ->get();
    }

    #[Computed]
    public function milestoneProgress()
    {
        return $this->project->milestones->map(function ($ms) {
            $total = Task::where('milestone_id', $ms->id)->count();
            $done  = Task::where('milestone_id', $ms->id)->where('status', 'done')->count();
            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;

            return [
                'id'           => $ms->id,
                'name'         => $ms->name,
                'due_date'     => $ms->due_date,
                'completed_at' => $ms->completed_at,
                'task_total'   => $total,
                'task_done'    => $done,
                'pct'          => $pct,
                'deliverables' => $this->doneTasks->where('milestone_id', $ms->id)->values(),
            ];
        });
    }

    public function render()
    {
        return view('livewire.backend.projectPortal', [
            'project'           => $this->project,
            'milestoneProgress' => $this->milestoneProgress,
            'doneTasks'         => $this->doneTasks,
        ]);
    }
}
