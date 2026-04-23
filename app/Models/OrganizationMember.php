<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Role Checks ──────────────────────────────────────────────

    public function isOwner(): bool          { return $this->role === 'owner'; }
    public function isAdmin(): bool          { return in_array($this->role, ['owner', 'admin']); }
    public function isProjectManager(): bool { return in_array($this->role, ['owner', 'admin', 'project_manager']); }
    public function isMember(): bool         { return in_array($this->role, ['owner', 'admin', 'project_manager', 'member']); }
    public function isViewer(): bool         { return $this->role === 'viewer'; }

    public function canManageProjects(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'project_manager']);
    }

    public function canManageMembers(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }
}
