<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityOverride extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'date',
        'is_available',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_available' => 'boolean',
            'created_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
