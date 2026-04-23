<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    /** Icon per notification type — used in bell dropdown */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'task_assigned'       => 'clipboard',
            'pr_merged'           => 'git-merge',
            'booking_confirmed'   => 'calendar-check',
            'booking_cancelled'   => 'calendar-x',
            'payment_released'    => 'cash',
            'dispute_opened'      => 'exclamation-circle',
            'project_invite'      => 'user-plus',
            'comment_mention'     => 'at-symbol',
            'milestone_completed' => 'flag',
            default               => 'bell',
        };
    }
}
