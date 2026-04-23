<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'filename',
        'disk_path',
        'mime_type',
        'size_bytes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return \Storage::url($this->disk_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->size_bytes) return 'Unknown';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($this->size_bytes, 1024));
        return round($this->size_bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
