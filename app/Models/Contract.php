<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'freelancer_id',
        'service_id',
        'booking_id',
        'project_id',
        'title',
        'description',
        'total_amount',
        'currency',
        'deposit_percentage',
        'deposit_amount',
        'platform_fee_percentage',
        'status',
        'work_submitted_at',
        'auto_release_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'             => 'decimal:2',
            'deposit_amount'           => 'decimal:2',
            'platform_fee_percentage'  => 'decimal:2',
            'work_submitted_at'        => 'datetime',
            'auto_release_at'          => 'datetime',
            'completed_at'             => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Auto-calculate deposit_amount on creating
        static::creating(function (Contract $contract) {
            if (! $contract->deposit_amount) {
                $contract->deposit_amount = round(
                    $contract->total_amount * $contract->deposit_percentage / 100,
                    2
                );
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class, 'contract_id')
                    ->orderBy('id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'contract_id')
                    ->latest('created_at');
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class, 'contract_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'submitted']);
    }

    public function scopePendingRelease(Builder $query): Builder
    {
        // For the auto-release cron job
        return $query->where('status', 'submitted')
                     ->whereNotNull('auto_release_at')
                     ->where('auto_release_at', '<=', now());
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('client_id', $userId)
                     ->orWhere('freelancer_id', $userId);
    }

    // ── Lifecycle ────────────────────────────────────────────────

    /** Client pays the deposit — activates the contract */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /** Freelancer submits work */
    public function submitWork(): void
    {
        $this->update([
            'status'            => 'submitted',
            'work_submitted_at' => now(),
            'auto_release_at'   => now()->addDays(7),
        ]);
    }

    /** Client confirms OR auto-release triggers */
    public function complete(bool $autoReleased = false): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function dispute(string $reason): Dispute
    {
        $this->update(['status' => 'disputed']);

        return Dispute::create([
            'contract_id' => $this->id,
            'raised_by'   => auth()->id(),
            'against'     => auth()->id() === $this->client_id
                             ? $this->freelancer_id
                             : $this->client_id,
            'reason'      => $reason,
            'description' => '',
        ]);
    }

    // ── Financial Calculations ───────────────────────────────────

    public function getRemainingAmountAttribute(): float
    {
        return round($this->total_amount - $this->deposit_amount, 2);
    }

    public function getPlatformFeeAmountAttribute(): float
    {
        return round($this->total_amount * $this->platform_fee_percentage / 100, 2);
    }

    public function getFreelancerNetAttribute(): float
    {
        return round($this->total_amount - $this->platform_fee_amount, 2);
    }

    // ── Status Checks ────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isActive(): bool     { return $this->status === 'active'; }
    public function isSubmitted(): bool  { return $this->status === 'submitted'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isDisputed(): bool   { return $this->status === 'disputed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    public function isAutoReleaseOverdue(): bool
    {
        return $this->isSubmitted()
            && $this->auto_release_at
            && $this->auto_release_at->isPast();
    }

    public function getDaysUntilAutoReleaseAttribute(): ?int
    {
        if (! $this->auto_release_at || ! $this->isSubmitted()) return null;
        return max(0, (int) now()->diffInDays($this->auto_release_at, false));
    }

    public function getFormattedTotalAttribute(): string
    {
        return strtoupper($this->currency) . ' ' . number_format($this->total_amount, 2);
    }
}
