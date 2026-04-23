<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    // ── APPEND-ONLY ──────────────────────────────────────────────
    // Never call update() or save() on an existing row.
    // Every money event = a new row. Ledger is always reconcilable.
    // ─────────────────────────────────────────────────────────────

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'milestone_id',
        'payer_id',
        'payee_id',
        'type',
        'amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'status',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ContractMilestone::class, 'milestone_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForContract(Builder $query, int $contractId): Builder
    {
        return $query->where('contract_id', $contractId);
    }

    // ── Static Factories ─────────────────────────────────────────
    // Convenience methods to create specific transaction types cleanly.

    public static function recordDeposit(Contract $contract, string $stripeIntentId): self
    {
        return static::create([
            'contract_id'               => $contract->id,
            'payer_id'                  => $contract->client_id,
            'payee_id'                  => null, // Held by platform
            'type'                      => 'deposit',
            'amount'                    => $contract->deposit_amount,
            'currency'                  => $contract->currency,
            'stripe_payment_intent_id'  => $stripeIntentId,
            'status'                    => 'completed',
            'description'               => "Deposit for contract: {$contract->title}",
        ]);
    }

    public static function recordRelease(Contract $contract, bool $auto = false): self
    {
        return static::create([
            'contract_id' => $contract->id,
            'payer_id'    => null,
            'payee_id'    => $contract->freelancer_id,
            'type'        => $auto ? 'auto_release' : 'milestone_release',
            'amount'      => $contract->remaining_amount,
            'currency'    => $contract->currency,
            'status'      => 'completed',
            'description' => $auto
                ? "Auto-released after 7-day review window: {$contract->title}"
                : "Client confirmed delivery: {$contract->title}",
        ]);
    }

    public static function recordPlatformFee(Contract $contract): self
    {
        return static::create([
            'contract_id' => $contract->id,
            'payer_id'    => $contract->freelancer_id,
            'payee_id'    => null, // Platform account
            'type'        => 'platform_fee',
            'amount'      => $contract->platform_fee_amount,
            'currency'    => $contract->currency,
            'status'      => 'completed',
            'description' => "Platform fee ({$contract->platform_fee_percentage}%): {$contract->title}",
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isFailed(): bool    { return $this->status === 'failed'; }
    public function isPending(): bool   { return $this->status === 'pending'; }

    public function getFormattedAmountAttribute(): string
    {
        return strtoupper($this->currency) . ' ' . number_format($this->amount, 2);
    }
}
