<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MeetingRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'project_id',
        'org_id',
        'created_by',
        'title',
        'room_token',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MeetingRoom $room) {
            if (empty($room->room_token)) {
                // LiveKit room names must be URL-safe
                $room->room_token = 'room-' . Str::random(32);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class, 'room_id')
                    ->orderBy('joined_at');
    }

    public function recording(): HasOne
    {
        return $this->hasOne(MeetingRecording::class, 'room_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'meeting_room_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'live');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeEnded(Builder $query): Builder
    {
        return $query->where('status', 'ended');
    }

    // ── Lifecycle ────────────────────────────────────────────────

    public function start(): void
    {
        $this->update([
            'status'     => 'live',
            'started_at' => now(),
        ]);
    }

    public function end(): void
    {
        $duration = $this->started_at
            ? (int) now()->diffInSeconds($this->started_at)
            : null;

        $this->update([
            'status'           => 'ended',
            'ended_at'         => now(),
            'duration_seconds' => $duration,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isLive(): bool      { return $this->status === 'live'; }
    public function isEnded(): bool     { return $this->status === 'ended'; }
    public function isScheduled(): bool { return $this->status === 'scheduled'; }

    public function hasRecording(): bool
    {
        return $this->recording()->exists();
    }

    public function hasTranscript(): bool
    {
        return $this->recording?->transcript_text !== null;
    }

    /** Duration formatted as "1h 23m" */
    public function getFormattedDurationAttribute(): ?string
    {
        if (! $this->duration_seconds) return null;
        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);
        return ($h > 0 ? "{$h}h " : '') . "{$m}m";
    }

    /** Join URL for participants */
    public function getJoinUrlAttribute(): string
    {
        return route('meetings.join', $this->room_token);
    }
}
