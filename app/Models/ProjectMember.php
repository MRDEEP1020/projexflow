<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isManager(): bool { return $this->role === 'manager'; }
    public function isMember(): bool  { return in_array($this->role, ['manager', 'member']); }
    public function isViewer(): bool  { return $this->role === 'viewer'; }

    public function canEditTasks(): bool
    {
        return in_array($this->role, ['manager', 'member']);
    }
}
