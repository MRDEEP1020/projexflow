<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ════════════════════════════════════════════════════════════════
// GitHubWebhookCall
// Managed by spatie/laravel-github-webhooks.
// Raw payload archive — never delete. Enables retry.
// ════════════════════════════════════════════════════════════════
class GitHubWebhookCall extends Model
{
    protected $table = 'github_webhook_calls';

    protected $fillable = [
        'name',
        'url',
        'headers',
        'payload',
        'exception',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'headers'      => 'array',
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return ! is_null($this->processed_at);
    }

    public function hasFailed(): bool
    {
        return ! is_null($this->exception);
    }

    public function getEventType(): string
    {
        return $this->name;
    }

    /** Extract task ID from PR title or body using pattern TASK-{id} or #{id} */
    public function extractTaskId(): ?int
    {
        $text = ($this->payload['pull_request']['title'] ?? '')
              . ' '
              . ($this->payload['pull_request']['body'] ?? '');

        preg_match('/(?:TASK-|#)(\d+)/i', $text, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }
}
