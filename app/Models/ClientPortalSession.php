<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ════════════════════════════════════════════════════════════════
// ClientPortalSession
// ════════════════════════════════════════════════════════════════
class ClientPortalSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'client_email',
        'ip_address',
        'last_seen_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // ✅ Correct — matches Laravel's signature exactly
    public function touch($attribute = null): bool
    {
        $this->last_seen_at = now();
        return $this->save();
    }
}


// ════════════════════════════════════════════════════════════════
// ClientFeedback
// ════════════════════════════════════════════════════════════════
class ClientFeedback extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'task_id',
        'client_name',
        'client_email',
        'body',
        'type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function isApproval(): bool
    {
        return $this->type === 'approval';
    }
    public function isRevisionRequest(): bool
    {
        return $this->type === 'revision_request';
    }
    public function isComment(): bool
    {
        return $this->type === 'comment';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->client_name ?? $this->client_email ?? 'Anonymous Client';
    }
}
