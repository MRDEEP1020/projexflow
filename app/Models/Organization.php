<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'owner_id',
        'type',
        'plan',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'plan' => 'string',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members', 'org_id', 'user_id')
                    ->withPivot('role', 'joined_at');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'org_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'org_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'org_id');
    }

    public function activeProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'org_id')
                    ->whereNull('archived_at')
                    ->whereNotIn('status', ['cancelled']);
    }

    public function archivedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'org_id')
                    ->whereNotNull('archived_at');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('memberships', fn ($q) => $q->where('user_id', $userId));
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function userRole(int $userId): ?string
    {
        return $this->memberships()
                    ->where('user_id', $userId)
                    ->value('role');
    }

    public function hasUser(int $userId): bool
    {
        return $this->memberships()->where('user_id', $userId)->exists();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? \Storage::url($this->logo) : null;
    }
}
