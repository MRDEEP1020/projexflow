<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskComment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'user_id',
        'parent_id',
        'body',
        'edited_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at'  => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id')
                    ->oldest('created_at');
    }

    public function isEdited(): bool
    {
        return ! is_null($this->edited_at);
    }
}
