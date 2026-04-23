<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'cover_image',
        'tech_stack',
        'project_url',
        'github_url',
        'is_featured',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'is_featured' => 'boolean',
            'is_public'   => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The platform project this item is linked to (optional) */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')
                     ->orderBy('sort_order');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isExternal(): bool
    {
        return is_null($this->project_id);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? \Storage::url($this->cover_image) : null;
    }

    public function getTechStackListAttribute(): string
    {
        return collect($this->tech_stack)->implode(', ');
    }
}
