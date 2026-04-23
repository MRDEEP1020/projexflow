<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'milestone_id');
    }

    public function clientFeedback(): HasMany
    {
        return $this->hasMany(ClientFeedback::class, 'milestone_id');
    }

    public function contractMilestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class, 'milestone_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')
                     ->whereNotNull('due_date')
                     ->where('due_date', '<', now());
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return ! is_null($this->completed_at);
    }

    public function complete(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /** Progress percentage based on tasks under this milestone */
    public function progressPercentage(): int
    {
        $total = $this->tasks()->whereNull('parent_task_id')->count();
        if ($total === 0) return 0;

        $done = $this->tasks()
                     ->whereNull('parent_task_id')
                     ->where('status', 'done')
                     ->count();

        return (int) round(($done / $total) * 100);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! $this->isCompleted();
    }
}
