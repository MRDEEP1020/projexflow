<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ════════════════════════════════════════════════════════════════
// AvailabilitySchedule  — weekly recurring availability
// ════════════════════════════════════════════════════════════════
class AvailabilitySchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'day_of_week'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDayNameAttribute(): string
    {
        return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$this->day_of_week];
    }
}
