<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'title',
        'description',
        'amount',
        'due_date',
        'status',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'due_date'    => 'date',
            'released_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFunded(Builder $query): Builder
    {
        return $query->where('status', 'funded');
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('status', 'released');
    }

    // ── Lifecycle ────────────────────────────────────────────────

    public function fund(): void
    {
        $this->update(['status' => 'funded']);
    }

    public function submit(): void
    {
        $this->update(['status' => 'submitted']);
    }

    public function release(): void
    {
        $this->update([
            'status'      => 'released',
            'released_at' => now(),
        ]);
    }

    // ── Status Checks ────────────────────────────────────────────

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isFunded(): bool    { return $this->status === 'funded'; }
    public function isSubmitted(): bool { return $this->status === 'submitted'; }
    public function isReleased(): bool  { return $this->status === 'released'; }
    public function isDisputed(): bool  { return $this->status === 'disputed'; }

    public function getFormattedAmountAttribute(): string
    {
        $currency = strtoupper($this->contract?->currency ?? 'USD');
        return "{$currency} " . number_format($this->amount, 2);
    }
}
