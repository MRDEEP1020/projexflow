<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'delivery_type',
        'price_from',
        'price_to',
        'currency',
        'delivery_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_from'   => 'decimal:2',
            'price_to'     => 'decimal:2',
            'is_active'    => 'boolean',
            'delivery_days' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'service_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopePriceRange(Builder $query, float $min, float $max): Builder
    {
        return $query->where('price_from', '>=', $min)
                     ->where('price_from', '<=', $max);
    }

    public function scopeFixedPrice(Builder $query): Builder
    {
        return $query->where('delivery_type', 'fixed_price');
    }

    public function scopeHourly(Builder $query): Builder
    {
        return $query->where('delivery_type', 'hourly');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function getDisplayPriceAttribute(): string
    {
        $symbol = $this->getCurrencySymbol();

        if ($this->delivery_type === 'hourly') {
            return "{$symbol}" . number_format($this->price_from, 2) . '/hr';
        }

        if ($this->price_to && $this->price_to > $this->price_from) {
            return "{$symbol}" . number_format($this->price_from, 2)
                 . ' – '
                 . "{$symbol}" . number_format($this->price_to, 2);
        }

        return "{$symbol}" . number_format($this->price_from, 2);
    }

    public function getCurrencySymbol(): string
    {
        return match(strtoupper($this->currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'XAF' => 'FCFA ',  // Central African Franc (Cameroon)
            'NGN' => '₦',
            'GHS' => '₵',
            'KES' => 'KSh ',
            default => $this->currency . ' ',
        };
    }

    public function getDeliveryLabelAttribute(): string
    {
        if (! $this->delivery_days) return '';
        return $this->delivery_days === 1
            ? '1 day delivery'
            : "{$this->delivery_days} day delivery";
    }
}
