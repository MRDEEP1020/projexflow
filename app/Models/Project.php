<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'status',
        'priority',
        'progress_percentage',
        'start_date',
        'due_date',
        'completed_at',
        'archived_at',
        'github_repo',
        'github_branch',
        'client_token',
        'client_portal_enabled',
        'client_name',
        'client_email',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'             => 'date',
            'due_date'               => 'date',
            'completed_at'           => 'datetime',
            'archived_at'            => 'datetime',
            'client_portal_enabled'  => 'boolean',
            'progress_percentage'    => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Auto-generate a secure client portal token on creation
        static::creating(function (Project $project) {
            if (empty($project->client_token)) {
                $project->client_token = Str::random(64);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
                    ->withPivot('role', 'added_at');
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'project_id')
                    ->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /** Only top-level tasks (not subtasks) — used for progress calculation */
    public function topLevelTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id')
                    ->whereNull('parent_task_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class, 'project_id')
                    ->latest('created_at');
    }

    public function clientPortalSessions(): HasMany
    {
        return $this->hasMany(ClientPortalSession::class, 'project_id');
    }

    public function clientFeedback(): HasMany
    {
        return $this->hasMany(ClientFeedback::class, 'project_id')
                    ->latest('created_at');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'project_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'project_id');
    }

    public function meetingRooms(): HasMany
    {
        return $this->hasMany(MeetingRoom::class, 'project_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    /** Active = not archived, not cancelled */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
                     ->whereNotIn('status', ['cancelled']);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeForOrg(Builder $query, int $orgId): Builder
    {
        return $query->where('org_id', $orgId);
    }

    public function scopePersonal(Builder $query): Builder
    {
        return $query->whereNull('org_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
                     ->where('due_date', '<', now())
                     ->whereNotIn('status', ['completed', 'cancelled'])
                     ->whereNull('archived_at');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ── Progress Calculation ─────────────────────────────────────

    /**
     * Recalculate progress_percentage based on top-level done tasks.
     * Called after any task status change.
     */
    public function recalculateProgress(): void
    {
        $total = $this->topLevelTasks()->count();

        if ($total === 0) {
            $this->update(['progress_percentage' => 0]);
            return;
        }

        $done = $this->topLevelTasks()
                     ->where('status', 'done')
                     ->count();

        $percentage = (int) round(($done / $total) * 100);

        $this->update([
            'progress_percentage' => $percentage,
            'completed_at'        => $done === $total ? now() : null,
            'status'              => $done === $total ? 'completed' : $this->status,
        ]);
    }

    // ── Archive ──────────────────────────────────────────────────

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    // ── GitHub ───────────────────────────────────────────────────

    public function hasGitHub(): bool
    {
        return ! empty($this->github_repo);
    }

    public function getGitHubUrlAttribute(): ?string
    {
        return $this->github_repo
            ? "https://github.com/{$this->github_repo}"
            : null;
    }

    // ── Client Portal ────────────────────────────────────────────

    public function getPortalUrlAttribute(): string
    {
        return route('portal.show', $this->client_token);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['completed', 'cancelled']);
    }
}
