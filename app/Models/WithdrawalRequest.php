<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'method',
        'account_details',
        'status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'account_details' => 'encrypted:array', // Encrypted at rest
            'processed_at'    => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // ── Lifecycle ────────────────────────────────────────────────

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status'       => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        // Return funds to wallet on failure
        $this->update(['status' => 'failed']);
        $this->user->wallet->increment('available_balance', $this->amount);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'bank_transfer'  => 'Bank Transfer',
            'mobile_money'   => 'Mobile Money',
            'stripe'         => 'Stripe',
            'paypal'         => 'PayPal',
            'flutterwave'    => 'Flutterwave',
            'cinetpay'       => 'CinetPay',
            default          => $this->method,
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return strtoupper($this->currency) . ' ' . number_format($this->amount, 2);
    }
}
