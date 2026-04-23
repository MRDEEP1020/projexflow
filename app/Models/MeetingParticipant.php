<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ════════════════════════════════════════════════════════════════
// MeetingParticipant
// ════════════════════════════════════════════════════════════════
class MeetingParticipant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'user_id',
        'guest_name',
        'guest_email',
        'role',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at'   => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isGuest(): bool { return is_null($this->user_id); }
    public function isHost(): bool  { return $this->role === 'host'; }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Guest';
    }

    /** Duration the participant was in the call, formatted */
    public function getSessionDurationAttribute(): ?string
    {
        if (! $this->joined_at || ! $this->left_at) return null;
        $seconds = $this->joined_at->diffInSeconds($this->left_at);
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return "{$m}m {$s}s";
    }

    public function leave(): void
    {
        $this->update(['left_at' => now()]);
    }
}
