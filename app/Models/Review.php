<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'project_id',
        'booking_id',
        'org_id',
        'rating',
        'title',
        'body',
        'is_verified',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'rating'      => 'integer',
            'created_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // After every save, recalculate the reviewee's avg_rating
        static::saved(function (Review $review) {
            $profile = ServiceProfile::where('user_id', $review->reviewee_id)->first();
            $profile?->recalculateRating();
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeForReviewee(Builder $query, int $userId): Builder
    {
        return $query->where('reviewee_id', $userId);
    }

    public function scopeMinRating(Builder $query, int $stars): Builder
    {
        return $query->where('rating', '>=', $stars);
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Determine verified status based on whether a real transaction is linked */
    public function computeIsVerified(): bool
    {
        return ! is_null($this->project_id) || ! is_null($this->booking_id);
    }

    public function getStarsHtmlAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * The context label shown on the review card:
     * "Verified project", "Verified booking", or "Org collaboration"
     */
    public function getContextLabelAttribute(): string
    {
        if ($this->project_id)  return 'Verified project';
        if ($this->booking_id)  return 'Verified booking';
        if ($this->org_id)      return 'Org collaboration';
        return 'Direct review';
    }
}
