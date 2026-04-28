<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    use HasFactory;

    protected $table = 'job_posts';

    protected $fillable = [
        'client_id',
        'hired_freelancer_id',
        'title',
        'description',
        'category',
        'type',
        'budget_min',
        'budget_max',
        'currency',
        'experience_level',
        'skills_required',
        'duration',
        'deadline',
        'visibility',
        'max_applicants',
        'status',
    ];

    protected $casts = [
        'skills_required' => 'array',
        'deadline' => 'date',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function hiredFreelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hired_freelancer_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}