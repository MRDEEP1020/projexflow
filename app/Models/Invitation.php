<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'email',
        'role',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at'  => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '<=', now());
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isPending(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at);
    }

    public function accept(): void
    {
        $this->update(['accepted_at' => now()]);
    }

    /** Generate a cryptographically secure token */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function getAcceptUrlAttribute(): string
    {
        return route('invitations.accept', $this->token);
    }
}
