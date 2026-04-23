<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'available_balance',
        'held_balance',
        'total_earned',
        'currency',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'held_balance'      => 'decimal:2',
            'total_earned'      => 'decimal:2',
            'updated_at'        => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'payee_id', 'user_id')
                    ->latest('created_at');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'user_id', 'user_id');
    }

    // ── Balance Operations ───────────────────────────────────────
    // All balance changes should go through these methods
    // to keep available_balance and held_balance always consistent.

    /** When a client pays a deposit — hold funds for freelancer */
    public function holdFunds(float $amount): void
    {
        $this->increment('held_balance', $amount);
        $this->touch();
    }

    /** When payment is released — move from held to available, minus fee */
    public function releaseToAvailable(float $grossAmount, float $platformFee): void
    {
        $net = $grossAmount - $platformFee;

        $this->decrement('held_balance', $grossAmount);
        $this->increment('available_balance', $net);
        $this->increment('total_earned', $net);
        $this->touch();
    }

    /** When freelancer requests a withdrawal */
    public function deductForWithdrawal(float $amount): void
    {
        if ($this->available_balance < $amount) {
            throw new \RuntimeException('Insufficient available balance.');
        }

        $this->decrement('available_balance', $amount);
        $this->touch();
    }

    /** Refund held funds back to client wallet (on dispute resolved for client) */
    public function refundHeldFunds(float $amount): void
    {
        $this->decrement('held_balance', $amount);
        $this->touch();
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->available_balance + (float) $this->held_balance;
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function getFormattedAvailableAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->available_balance, 2);
    }

    public function getFormattedHeldAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->held_balance, 2);
    }
}
