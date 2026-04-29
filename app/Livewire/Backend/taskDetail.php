<?php

namespace App\Livewire\backend;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskFile;
use App\Models\ProjectMember;
use App\Models\ProjectActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskDetail extends Component
{
    use WithFileUploads;

    public int     $taskId;
    public Task    $task;

    // Inline edit state
    public string  $editTitle       = '';
    public string  $editDescription = '';
    public string  $editStatus      = '';
    public string  $editPriority    = '';
    public ?string $editDueDate     = null;
    public ?int    $editAssignee    = null;
    public ?int    $editMilestone   = null;

    public bool    $editingTitle    = false;
    public bool    $editingDesc     = false;

    // Comment
    public string  $commentBody     = '';
    public ?int    $replyTo         = null;

    // Deliverable
    public string  $delivType       = 'url';
    public string  $delivUrl        = '';
    public string  $delivNote       = '';
    public         $delivFile       = null;
    public bool    $showDelivForm   = false;

    // New subtask
    public string  $subtaskTitle    = '';
    public bool    $showSubtaskForm = false;

    protected function getListeners(): array
    {
        return [
            "echo-private:project.{$this->task->project_id},.task.updated" => 'refreshTask',
            "echo-private:project.{$this->task->project_id},.comment.added" => '$refresh',
        ];
    }

    public function mount(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->loadTask();
    }

    public function refreshTask(): void
    {
        $this->loadTask();
    }

    protected function loadTask(): void
    {
        $this->task = Task::with([
            'assignee',
            'milestone',
            'project.projectMembers.user',
            'subtasks.assignee',
            'taskFiles',
        ])->findOrFail($this->taskId);

        // Ensure current user is a project member
        abort_unless(
            $this->task->project->projectMembers()->where('user_id', Auth::id())->exists(),
            403
        );

        // Sync edit fields with task
        $this->editTitle       = $this->task->title;
        $this->editDescription = $this->task->description ?? '';
        $this->editStatus      = $this->task->status;
        $this->editPriority    = $this->task->priority;
        $this->editDueDate     = $this->task->due_date?->format('Y-m-d');
        $this->editAssignee    = $this->task->assigned_to;
        $this->editMilestone   = $this->task->milestone_id;
    }

    // ── Authorization helper ─────────────────────────────────
    protected function canEdit(): bool
    {
        $role = $this->task->project
            ->projectMembers()->where('user_id', Auth::id())->value('role');
        return in_array($role, ['manager', 'owner'])
            || $this->task->assigned_to === Auth::id();
    }

    protected function isManager(): bool
    {
        $role = $this->task->project
            ->projectMembers()->where('user_id', Auth::id())->value('role');
        return in_array($role, ['manager', 'owner']);
    }

    // ── Field updates ─────────────────────────────────────────
    public function saveTitle(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate(['editTitle' => ['required', 'string', 'max:300']]);
        $this->task->update(['title' => $this->editTitle]);
        $this->editingTitle = false;
        $this->logAndBroadcast('updated', "updated task title");
    }

    public function saveDescription(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->task->update(['description' => $this->editDescription ?: null]);
        $this->editingDesc = false;
        $this->logAndBroadcast('updated', "updated task description");
    }

    public function updateStatus(string $status): void
    {
        abort_unless($this->canEdit(), 403);
        $this->task->transitionTo($status);
        $this->task->project->recalculateProgress();
        $this->editStatus = $status;
        $this->loadTask();
        $this->logAndBroadcast('status_changed', "moved task to ".ucfirst(str_replace('_',' ',$status)));
        $this->dispatch('toast', ['message' => 'Status updated.', 'type' => 'success']);
    }

    public function updatePriority(string $priority): void
    {
        abort_unless($this->canEdit(), 403);
        $this->task->update(['priority' => $priority]);
        $this->editPriority = $priority;
        $this->logAndBroadcast('updated', "changed priority to {$priority}");
    }

    public function updateDueDate(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->task->update(['due_date' => $this->editDueDate ?: null]);
        $this->logAndBroadcast('updated', "updated due date");
    }

    public function updateAssignee(): void
    {
        abort_unless($this->isManager(), 403);

        $this->task->update(['assigned_to' => $this->editAssignee ?: null]);

        if ($this->editAssignee) {
            // Notification for assignee
            \App\Models\Notification::create([
                'user_id' => $this->editAssignee,
                'type'    => 'task_assigned',
                'title'   => 'New task assigned to you',
                'body'    => $this->task->title,
                'url'     => route('backend.projectList', $this->task->project_id),
            ]);
        }

        $this->loadTask();
        $this->logAndBroadcast('assigned', "assigned task");
        $this->dispatch('toast', ['message' => 'Assignee updated.', 'type' => 'success']);
    }

    public function updateMilestone(): void
    {
        abort_unless($this->isManager(), 403);
        $this->task->update(['milestone_id' => $this->editMilestone ?: null]);
        $this->loadTask();
    }

    // ── Comments ──────────────────────────────────────────────
    public function addComment(): void
    {
        $this->validate(['commentBody' => ['required', 'string', 'max:5000']]);

        TaskComment::create([
            'task_id'   => $this->task->id,
            'user_id'   => Auth::id(),
            'parent_id' => $this->replyTo,
            'body'      => $this->commentBody,
        ]);

        // Parse @mentions
        preg_match_all('/@([\w.]+)/', $this->commentBody, $matches);
        foreach ($matches[1] ?? [] as $username) {
            $mentioned = \App\Models\User::where('name', $username)->first();
            if ($mentioned && $mentioned->id !== Auth::id()) {
                \App\Models\Notification::create([
                    'user_id' => $mentioned->id,
                    'type'    => 'comment_mention',
                    'title'   => Auth::user()->name . ' mentioned you in a comment',
                    'body'    => substr($this->commentBody, 0, 100),
                    'url'     => route('projects.show', $this->task->project_id),
                ]);
            }
        }

        $this->commentBody = '';
        $this->replyTo     = null;
        $this->logAndBroadcast('comment_added', "commented on task");
        $this->dispatch('toast', ['message' => 'Comment added.', 'type' => 'success']);
    }

    // ── Deliverable ───────────────────────────────────────────
    public function submitDeliverable(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->validate([
            'delivType' => ['required', 'in:url,file_upload,figma,github_pr,notion,loom,other'],
            'delivUrl'  => ['required_if:delivType,url,figma,github_pr,notion,loom', 'nullable', 'url', 'max:2048'],
            'delivFile' => ['required_if:delivType,file_upload', 'nullable', 'file', 'max:20480'],
        ]);

        $url = $this->delivUrl;

        if ($this->delivType === 'file_upload' && $this->delivFile) {
            $path = $this->delivFile->store("tasks/{$this->task->id}", 's3');
            $url  = Storage::disk('s3')->url($path);
        }

        $this->task->update([
            'deliverable_type' => $this->delivType,
            'deliverable_url'  => $url,
            'deliverable_note' => $this->delivNote ?: null,
        ]);

        // Auto-transition to in_review (ALG-TASK-03)
        $this->updateStatus('in_review');

        $this->delivUrl      = '';
        $this->delivNote     = '';
        $this->delivFile     = null;
        $this->showDelivForm = false;
        $this->dispatch('toast', ['message' => 'Deliverable submitted. Task moved to In Review.', 'type' => 'success']);
    }

    // ── Subtasks ──────────────────────────────────────────────
    public function addSubtask(): void
    {
        abort_unless($this->canEdit(), 403);
        $this->validate(['subtaskTitle' => ['required', 'string', 'max:300']]);

        Task::create([
            'project_id'     => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'title'          => $this->subtaskTitle,
            'status'         => 'todo',
            'priority'       => 'medium',
        ]);

        $this->subtaskTitle    = '';
        $this->showSubtaskForm = false;
        $this->loadTask();
        $this->dispatch('toast', ['message' => 'Subtask added.', 'type' => 'success']);
    }

    public function toggleSubtask(int $id): void
    {
        $sub = Task::findOrFail($id);
        abort_unless($sub->parent_task_id === $this->task->id, 403);
        $sub->update(['status' => $sub->status === 'done' ? 'todo' : 'done']);
        $this->loadTask();
    }

    // ── File delete ───────────────────────────────────────────
    public function deleteFile(int $fileId): void
    {
        abort_unless($this->isManager(), 403);
        $file = TaskFile::findOrFail($fileId);
        Storage::disk('s3')->delete($file->disk_path);
        $file->delete();
        $this->loadTask();
    }

    // ── Helpers ───────────────────────────────────────────────
    protected function logAndBroadcast(string $type, string $desc): void
    {
        ProjectActivity::create([
            'project_id'  => $this->task->project_id,
            'user_id'     => Auth::id(),
            'type'        => $type,
            'description' => Auth::user()->name . ' ' . $desc . ': "' . $this->task->title . '"',
        ]);
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function comments()
    {
        return TaskComment::where('task_id', $this->task->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->task->project->projectMembers()->with('user')->get();
    }

    #[Computed]
    public function milestones()
    {
        return $this->task->project->milestones ?? collect();
    }

    public function render()
    {
        return view('livewire.backend.taskDetail');
    }
}
