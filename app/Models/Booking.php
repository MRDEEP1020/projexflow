<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'client_id',
        'client_email',
        'client_name',
        'service_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'timezone',
        'status',
        'meeting_room_id',
        'provider_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function meetingRoom(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'meeting_room_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'booking_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'booking_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
                     ->where('start_at', '>', now())
                     ->orderBy('start_at');
    }

    public function scopeForCalendar(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('provider_id', $userId)
              ->orWhere('client_id', $userId);
        })->whereIn('status', ['confirmed', 'pending'])
          ->orderBy('start_at');
    }

    // ── Lifecycle ────────────────────────────────────────────────

    /**
     * Provider confirms the booking.
     * Creates a MeetingRoom automatically.
     */
    public function confirm(): MeetingRoom
    {
        $room = MeetingRoom::create([
            'booking_id' => $this->id,
            'created_by' => $this->provider_id,
            'title'      => $this->title,
        ]);

        $this->update([
            'status'          => 'confirmed',
            'meeting_room_id' => $room->id,
        ]);

        // Notify both parties
        Notification::create([
            'user_id' => $this->provider_id,
            'type'    => 'booking_confirmed',
            'title'   => 'Booking confirmed',
            'body'    => "\"{$this->title}\" is confirmed for " . $this->start_at->format('M d, H:i'),
            'url'     => route('bookings.show', $this->id),
        ]);

        if ($this->client_id) {
            Notification::create([
                'user_id' => $this->client_id,
                'type'    => 'booking_confirmed',
                'title'   => 'Your booking is confirmed',
                'body'    => "\"{$this->title}\" confirmed for " . $this->start_at->format('M d, H:i'),
                'url'     => route('bookings.show', $this->id),
            ]);
        }

        return $room;
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status'         => 'cancelled',
            'provider_notes' => $reason,
        ]);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isUpcoming(): bool   { return in_array($this->status, ['pending','confirmed']) && $this->start_at->isFuture(); }

    public function isGuestBooking(): bool { return is_null($this->client_id); }

    public function getClientDisplayNameAttribute(): string
    {
        return $this->client?->name ?? $this->client_name ?? $this->client_email ?? 'Guest';
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->start_at->format('D, M d Y · H:i') . ' – ' . $this->end_at->format('H:i');
    }
}
