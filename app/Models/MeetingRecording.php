<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingRecording extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'recording_url',
        'transcript_text',
        'transcript_url',
        'file_size_bytes',
        'duration_seconds',
        'is_processing',
        'generated_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_processing' => 'boolean',
            'generated_at'  => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('is_processing', false)
                     ->whereNotNull('recording_url');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('is_processing', true);
    }

    // ── Lifecycle ────────────────────────────────────────────────

    /**
     * Called when the recording and transcript are both ready.
     * Fires a notification to all meeting participants.
     */
    public function markReady(string $recordingUrl, ?string $transcriptText, ?string $transcriptUrl): void
    {
        $this->update([
            'recording_url'   => $recordingUrl,
            'transcript_text' => $transcriptText,
            'transcript_url'  => $transcriptUrl,
            'is_processing'   => false,
            'generated_at'    => now(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isReady(): bool        { return ! $this->is_processing && ! is_null($this->recording_url); }
    public function hasTranscript(): bool  { return ! is_null($this->transcript_text); }

    public function getRecordingUrlAttribute($value): ?string
    {
        return $value ? \Storage::url($value) : null;
    }

    public function getTranscriptUrlAttribute($value): ?string
    {
        return $value ? \Storage::url($value) : null;
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size_bytes) return 'Unknown';
        $mb = round($this->file_size_bytes / 1024 / 1024, 1);
        return $mb >= 1024
            ? round($mb / 1024, 2) . ' GB'
            : "{$mb} MB";
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (! $this->duration_seconds) return null;
        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);
        return ($h > 0 ? "{$h}h " : '') . "{$m}m";
    }
}
