<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'profession_category',
        'skills',
        'hourly_rate',
        'currency',
        'years_experience',
        'location',
        'languages',
        'availability_status',
        'response_time_hours',
        'avg_rating',
        'total_reviews',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'skills'       => 'array',
            'languages'    => 'array',
            'hourly_rate'  => 'decimal:2',
            'avg_rating'   => 'decimal:2',
            'is_verified'  => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'user_id', 'user_id')
                    ->where('is_active', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id', 'user_id')
                    ->latest('created_at');
    }

    public function verifiedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id', 'user_id')
                    ->where('is_verified', true)
                    ->latest('created_at');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'user_id', 'user_id')
                    ->where('is_public', true)
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('availability_status', 'open_to_work');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('profession_category', $category);
    }

    public function scopeBySkill(Builder $query, string $skill): Builder
    {
        return $query->whereJsonContains('skills', $skill);
    }

    public function scopeMinRating(Builder $query, float $rating): Builder
    {
        return $query->where('avg_rating', '>=', $rating);
    }

    public function scopeMaxRate(Builder $query, float $rate): Builder
    {
        return $query->where('hourly_rate', '<=', $rate);
    }

    // ── Rating Recalculation ─────────────────────────────────────

    /** Called by ReviewObserver after every INSERT/UPDATE on reviews */
    public function recalculateRating(): void
    {
        $result = Review::where('reviewee_id', $this->user_id)
                        ->selectRaw('AVG(rating) as avg, COUNT(*) as total')
                        ->first();

        $this->update([
            'avg_rating'    => $result->avg ? round($result->avg, 2) : null,
            'total_reviews' => $result->total ?? 0,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isOpenToWork(): bool
    {
        return $this->availability_status === 'open_to_work';
    }

    public function getStarsAttribute(): string
    {
        if (! $this->avg_rating) return '—';
        $full  = (int) floor($this->avg_rating);
        $half  = ($this->avg_rating - $full) >= 0.5 ? 1 : 0;
        return str_repeat('★', $full) . ($half ? '½' : '') . ' (' . number_format($this->avg_rating, 1) . ')';
    }
}
