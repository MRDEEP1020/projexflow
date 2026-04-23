<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Project;
use App\Models\Task;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GitHubWebhookController extends Controller
{
    // ALG-GH-01: Verify webhook signature (GitHub uses HMAC-SHA256)
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) return false;

        $secret = config('services.github.webhook_secret');
        if (! $secret) return false;

        $payload = $request->getContent();
        $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($hash, $signature);
    }

    public function handle(Request $request): Response
    {
        // Verify signature
        if (! $this->verifySignature($request)) {
            Log::warning('GitHub webhook signature mismatch', [
                'ip' => $request->ip(),
                'event' => $request->header('X-GitHub-Event'),
            ]);
            return response('Unauthorized', 401);
        }

        $event = $request->header('X-GitHub-Event');
        $payload = $request->json()->all();

        Log::info('GitHub webhook received', ['event' => $event]);

        switch ($event) {
            case 'push':
                $this->handlePush($payload);
                break;
            case 'pull_request':
                $this->handlePullRequest($payload);
                break;
            case 'issues':
                $this->handleIssue($payload);
                break;
            default:
                // Ignore other events
                break;
        }

        return response('OK', 200);
    }

    // ALG-GH-02: Push event — trigger CI/CD notification
    protected function handlePush(array $payload): void
    {
        $repo = $payload['repository']['full_name'] ?? null;
        $branch = $payload['ref'] ?? null;
        $pusher = $payload['pusher']['name'] ?? 'Unknown';
        $commits = $payload['commits'] ?? [];

        if (! $repo || ! $branch) return;

        // Find project by github_repo
        $project = Project::where('github_repo', $repo)->first();
        if (! $project) return;

        // Extract branch name from refs/heads/main format
        $branchName = str_replace('refs/heads/', '', $branch);
        $isDefaultBranch = $branchName === ($project->github_branch ?? 'main');

        if (! $isDefaultBranch) return; // Ignore non-default branches

        // Notify project manager
        $manager = $project->projectMembers()
            ->where('role', 'manager')
            ->first();

        if (! $manager) return;

        $commitCount = count($commits);
        $message = $pusher . ' pushed ' . $commitCount . ' commit' . ($commitCount > 1 ? 's' : '') . ' to ' . $branchName;

        Notification::create([
            'user_id' => $manager->user_id,
            'type'    => 'github_push',
            'title'   => 'Code pushed to ' . $repo,
            'body'    => $message,
            'url'     => $payload['repository']['html_url'] . '/commits/' . $branchName,
        ]);
    }

    // ALG-GH-03: Pull request event — auto-complete task on PR merge
    protected function handlePullRequest(array $payload): void
    {
        $action = $payload['action'] ?? null;
        $repo = $payload['repository']['full_name'] ?? null;
        $pr = $payload['pull_request'] ?? [];

        // Only act on closed + merged PRs
        if ($action !== 'closed' || ! ($pr['merged'] ?? false)) {
            return;
        }

        if (! $repo) return;

        $project = Project::where('github_repo', $repo)->first();
        if (! $project) return;

        // Look for task linked in PR body or title
        $prTitle = $pr['title'] ?? '';
        $prBody = $pr['body'] ?? '';
        $prNumber = $pr['number'] ?? null;

        // Pattern: #123 in title or body = task ID 123
        $matches = [];
        if (preg_match('/#(\d+)/', $prTitle . ' ' . $prBody, $matches)) {
            $taskId = (int) $matches[1];
            $task = Task::where('id', $taskId)
                ->where('project_id', $project->id)
                ->whereIn('status', ['in_progress', 'in_review'])
                ->first();

            if ($task) {
                DB::transaction(function () use ($task, $repo, $prNumber) {
                    $task->update([
                        'status'               => 'done',
                        'deliverable_type'    => 'github_pr',
                        'deliverable_url'     => str_replace('full_name', 'html_url', $repo) . '/pull/' . $prNumber,
                        'deliverable_note'    => 'Merged PR #' . $prNumber,
                        'completed_at'        => now(),
                    ]);

                    // Notify assignee
                    if ($task->assigned_to) {
                        Notification::create([
                            'user_id' => $task->assigned_to,
                            'type'    => 'task_completed_github',
                            'title'   => 'Task marked complete: ' . $task->title,
                            'body'    => 'Merged PR #' . $prNumber,
                            'url'     => route('backend.projectBoard', $task->project_id),
                        ]);
                    }
                });

                Log::info('Task auto-completed via GitHub PR merge', [
                    'task_id' => $taskId,
                    'pr'      => $prNumber,
                ]);
            }
        }
    }

    // Bonus: Issue events for future use
    protected function handleIssue(array $payload): void
    {
        // Placeholder for issue → task sync
    }
}
