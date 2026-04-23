<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivity extends Model
{
    // Append-only — no updates, no deletes
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'type',
        'description',
        'github_url',
        'actor',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Display name — user name or GitHub actor */
    public function getActorNameAttribute(): string
    {
        return $this->user?->name ?? $this->actor ?? 'System';
    }

    /** Icon slug per event type — used in the frontend */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'task_created'         => 'plus-circle',
            'task_completed'       => 'check-circle',
            'task_updated'         => 'pencil',
            'task_assigned'        => 'user',
            'comment_added'        => 'chat',
            'file_uploaded'        => 'paper-clip',
            'pr_opened'            => 'git-pull-request',
            'pr_merged'            => 'git-merge',
            'commit_pushed'        => 'code',
            'milestone_completed'  => 'flag',
            'member_joined'        => 'user-plus',
            'project_created'      => 'folder-plus',
            'project_archived'     => 'archive',
            default                => 'activity',
        };
    }
}
