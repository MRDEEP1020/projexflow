<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'raised_by',
        'against',
        'reason',
        'description',
        'evidence_urls',
        'meeting_room_id',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_urls' => 'array',
            'resolved_at'   => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function against(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against');
    }

    /**
     * The meeting room whose recording serves as evidence.
     * This is the unique proof feature of ProjexFlow —
     * disputes can be resolved by reviewing the actual meeting recording + transcript.
     */
    public function meetingRoom(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'meeting_room_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereIn('status', [
            'resolved_for_client',
            'resolved_for_freelancer',
            'closed',
        ]);
    }

    // ── Lifecycle ────────────────────────────────────────────────

    public function startReview(): void
    {
        $this->update(['status' => 'under_review']);
    }

    /** Admin resolves in favour of the client — funds refunded */
    public function resolveForClient(string $adminNotes = ''): void
    {
        $this->update([
            'status'       => 'resolved_for_client',
            'admin_notes'  => $adminNotes,
            'resolved_at'  => now(),
        ]);

        $this->contract->update(['status' => 'refunded']);
    }

    /** Admin resolves in favour of the freelancer — funds released */
    public function resolveForFreelancer(string $adminNotes = ''): void
    {
        $this->update([
            'status'       => 'resolved_for_freelancer',
            'admin_notes'  => $adminNotes,
            'resolved_at'  => now(),
        ]);

        $this->contract->complete();
    }

    public function close(string $adminNotes = ''): void
    {
        $this->update([
            'status'      => 'closed',
            'admin_notes' => $adminNotes,
            'resolved_at' => now(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isOpen(): bool         { return $this->status === 'open'; }
    public function isUnderReview(): bool  { return $this->status === 'under_review'; }
    public function isResolved(): bool     { return in_array($this->status, ['resolved_for_client', 'resolved_for_freelancer', 'closed']); }
    public function resolvedForClient(): bool     { return $this->status === 'resolved_for_client'; }
    public function resolvedForFreelancer(): bool { return $this->status === 'resolved_for_freelancer'; }

    public function hasRecordingEvidence(): bool
    {
        return $this->meeting_room_id
            && $this->meetingRoom?->hasRecording();
    }

    public function hasTranscriptEvidence(): bool
    {
        return $this->meeting_room_id
            && $this->meetingRoom?->hasTranscript();
    }

    public function getReasonLabelAttribute(): string
    {
        return match($this->reason) {
            'work_not_delivered'  => 'Work not delivered',
            'quality_issues'      => 'Quality issues',
            'scope_disagreement'  => 'Scope disagreement',
            'payment_issue'       => 'Payment issue',
            default               => 'Other',
        };
    }
}
