<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Events\TaskStatusUpdated;
use App\Events\ProjectProgressUpdated;
use App\Events\ActivityFeedUpdated;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'parent_task_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'cadence',
        'due_date',
        'started_at',
        'completed_at',
        'pr_url',
        'pr_number',
        'commit_sha',
        'github_branch',
        'deliverable_type',
        'deliverable_url',
        'deliverable_note',
        'estimated_hours',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date'       => 'date',
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'estimated_hours' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }

    /** Parent task (self-reference) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /** Children (subtasks) */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')
            ->whereNull('parent_id')
            ->latest('created_at');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')
            ->latest('created_at');
    }

    public function taskFiles(): HasMany
    {
        return $this->hasMany(TaskFile::class, 'task_id')
            ->latest('created_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class, 'task_id')
            ->latest('created_at');
    }

    public function clientFeedback(): HasMany
    {
        return $this->hasMany(ClientFeedback::class, 'task_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done']);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('due_date', today())
            ->whereNotIn('status', ['done']);
    }

    public function scopeDueThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('due_date', [today(), now()->endOfWeek()])
            ->whereNotIn('status', ['done']);
    }

    public function scopeForCalendar(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId)
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['done']);
    }

    // ── Status Workflow ──────────────────────────────────────────

    /**
     * Transition to a new status.
     * Fires broadcast events and recalculates project progress when done.
     */
    public function transitionTo(string $newStatus, array $extra = []): void
    {
        $attributes = array_merge(['status' => $newStatus], $extra);

        if ($newStatus === 'in_progress' && is_null($this->started_at)) {
            $attributes['started_at'] = now();
        }

        if ($newStatus === 'done') {
            $attributes['completed_at'] = now();
        }

        $this->update($attributes);

        // Log activity
        ProjectActivity::create([
            'project_id'  => $this->project_id,
            'task_id'     => $this->id,
            'user_id'     => auth()->id(),
            'type'        => $newStatus === 'done' ? 'task_completed' : 'task_updated',
            'description' => auth()->user()->name . ' moved "' . $this->title . '" to ' . $newStatus,
        ]);

        // Recalculate project progress
        $this->project->recalculateProgress();

        // Broadcast real-time events
        broadcast(new TaskStatusUpdated($this))->toOthers();
        broadcast(new ProjectProgressUpdated($this->project));
        broadcast(new ActivityFeedUpdated($this->project->activities()->first()));
    }

    // ── GitHub Integration ───────────────────────────────────────

    public function linkPullRequest(string $prUrl, int $prNumber, string $branch): void
    {
        $this->update([
            'pr_url'        => $prUrl,
            'pr_number'     => $prNumber,
            'github_branch' => $branch,
        ]);
    }

    public function markDoneFromWebhook(string $prUrl, int $prNumber, string $actor): void
    {
        $this->update([
            'status'       => 'done',
            'completed_at' => now(),
            'pr_url'       => $prUrl,
            'pr_number'    => $prNumber,
        ]);

        ProjectActivity::create([
            'project_id'  => $this->project_id,
            'task_id'     => $this->id,
            'user_id'     => null,
            'type'        => 'pr_merged',
            'description' => "{$actor} merged PR #{$prNumber} — \"{$this->title}\"",
            'github_url'  => $prUrl,
            'actor'       => $actor,
        ]);

        $this->project->recalculateProgress();

        broadcast(new TaskStatusUpdated($this));
        broadcast(new ProjectProgressUpdated($this->project));
        broadcast(new ActivityFeedUpdated($this->project->activities()->first()));
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isTopLevel(): bool
    {
        return is_null($this->parent_task_id);
    }
    public function isSubtask(): bool
    {
        return ! is_null($this->parent_task_id);
    }
    public function isDone(): bool
    {
        return $this->status === 'done';
    }
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }
    public function isTodo(): bool
    {
        return $this->status === 'todo';
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'done';
    }

    public function hasDeliverable(): bool
    {
        return $this->deliverable_type !== 'none'
            && (! empty($this->deliverable_url) || ! empty($this->deliverable_note));
    }
}
