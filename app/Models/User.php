<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'is_marketplace_enabled',
        'stripe_connect_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_connect_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'is_marketplace_enabled' => 'boolean',
        ];
    }

    // ── Identity & Access ────────────────────────────────────────

    public function invitationsSent(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    // ── Organizations ────────────────────────────────────────────

    /** Organizations this user owns */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /** All orgs this user belongs to (including owned) */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members', 'user_id', 'org_id')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    /** Membership records for this user */
    public function orgMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'user_id');
    }

    // ── Projects ─────────────────────────────────────────────────

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /** Projects this user is a member of */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members', 'user_id', 'project_id')
                    ->withPivot('role', 'added_at');
    }

    // ── Tasks ────────────────────────────────────────────────────

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }

    public function taskFiles(): HasMany
    {
        return $this->hasMany(TaskFile::class, 'uploaded_by');
    }

    // ── Notifications ────────────────────────────────────────────

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')
                    ->whereNull('read_at');
    }

    // ── Portfolio & Archive ──────────────────────────────────────

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'user_id');
    }

    // ── Calendar & Scheduling ────────────────────────────────────

    public function availabilitySchedules(): HasMany
    {
        return $this->hasMany(AvailabilitySchedule::class, 'user_id');
    }

    public function availabilityOverrides(): HasMany
    {
        return $this->hasMany(AvailabilityOverride::class, 'user_id');
    }

    public function bookingsAsProvider(): HasMany
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function bookingsAsClient(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    // ── Marketplace ──────────────────────────────────────────────

    public function serviceProfile(): HasOne
    {
        return $this->hasOne(ServiceProfile::class, 'user_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'user_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    // ── Payments ─────────────────────────────────────────────────

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function contractsAsClient(): HasMany
    {
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function contractsAsFreelancer(): HasMany
    {
        return $this->hasMany(Contract::class, 'freelancer_id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'user_id');
    }

    // ── Video ────────────────────────────────────────────────────

    public function meetingRoomsCreated(): HasMany
    {
        return $this->hasMany(MeetingRoom::class, 'created_by');
    }

    public function meetingParticipations(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class, 'user_id');
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Check if user has a given role inside a specific org */
    public function roleInOrg(int $orgId): ?string
    {
        return $this->orgMemberships()
                    ->where('org_id', $orgId)
                    ->value('role');
    }

    public function isOwnerOf(Organization $org): bool
    {
        return $org->owner_id === $this->id;
    }

    public function isMemberOfOrg(int $orgId): bool
    {
        return $this->orgMemberships()->where('org_id', $orgId)->exists();
    }

    public function isMemberOfProject(int $projectId): bool
    {
        return $this->projects()->where('projects.id', $projectId)->exists();
    }

    /** Get or create this user's wallet */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? Wallet::create([
            'user_id'  => $this->id,
            'currency' => 'USD',
        ]);
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? \Storage::url($this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=1A5FAD&color=fff';
    }
}
