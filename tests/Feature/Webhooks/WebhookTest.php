<?php
// tests/Feature/Webhooks/WebhookTest.php
uses(Tests\TestCase::class);

use App\Models\Project;
use App\Models\Task;
use App\Models\MeetingRoom;
use App\Models\MeetingRecording;
use App\Models\Booking;
use Illuminate\Support\Facades\Queue;
use App\Jobs\TranscribeRecording;

describe('GitHub webhook signature', function () {

    it('rejects missing signature', function () {
        $this->postJson('/api/webhook/github', [])->assertStatus(401);
    });

    it('rejects wrong signature', function () {
        $this->withHeaders(['X-Hub-Signature-256' => 'sha256=wrong'])
            ->postJson('/api/webhook/github', [])->assertStatus(401);
    });

    it('accepts valid HMAC signature', function () {
        $secret  = config('services.github.webhook_secret', 'test-secret');
        $body    = json_encode(['ref' => 'refs/heads/main', 'repository' => ['full_name' => 'x/y'], 'commits' => [], 'pusher' => ['name' => 'dev']]);
        $sig     = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $this->withHeaders(['X-Hub-Signature-256' => $sig, 'X-GitHub-Event' => 'push'])
            ->call('POST', '/api/webhook/github', [], [], [], [], $body)
            ->assertStatus(200);
    });
});

describe('GitHub PR auto-completes task (ALG-GH-03)', function () {

    it('marks linked task as done when PR merges', function () {
        $client  = clientUser();
        $project = projectFor($client, ['github_repo' => 'acme/website']);
        $task    = Task::factory()->create(['project_id' => $project->id, 'status' => 'in_review']);

        $secret  = config('services.github.webhook_secret', 'test-secret');
        $body    = json_encode([
            'action' => 'closed',
            'repository' => ['full_name' => 'acme/website', 'html_url' => 'https://github.com/acme/website'],
            'pull_request' => ['merged' => true, 'number' => 42, 'title' => "Fix #".$task->id, 'body' => ''],
        ]);
        $sig = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $this->withHeaders(['X-Hub-Signature-256' => $sig, 'X-GitHub-Event' => 'pull_request'])
            ->call('POST', '/api/webhook/github', [], [], [], [], $body)
            ->assertStatus(200);

        expect($task->fresh()->status)->toBe('done');
        expect($task->fresh()->deliverable_type)->toBe('github_pr');
    });

    it('ignores closed-but-not-merged PRs', function () {
        $client  = clientUser();
        $project = projectFor($client, ['github_repo' => 'acme/app']);
        $task    = Task::factory()->create(['project_id' => $project->id, 'status' => 'in_review']);

        $secret = config('services.github.webhook_secret', 'test-secret');
        $body   = json_encode([
            'action' => 'closed',
            'repository' => ['full_name' => 'acme/app', 'html_url' => 'https://github.com/acme/app'],
            'pull_request' => ['merged' => false, 'number' => 9, 'title' => "Draft #".$task->id, 'body' => ''],
        ]);
        $sig = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $this->withHeaders(['X-Hub-Signature-256' => $sig, 'X-GitHub-Event' => 'pull_request'])
            ->call('POST', '/api/webhook/github', [], [], [], [], $body)->assertStatus(200);

        expect($task->fresh()->status)->toBe('in_review');
    });
});

describe('LiveKit webhooks', function () {

    it('marks room as ended on room_finished', function () {
        $client = clientUser(); $provider = freelancerUser();
        $booking = Booking::factory()->create([
            'provider_id' => $provider->id, 'client_id' => $client->id,
            'status' => 'confirmed', 'start_at' => now()->subHour(), 'end_at' => now()->subMinutes(5),
        ]);
        $room = MeetingRoom::factory()->create([
            'booking_id' => $booking->id, 'room_token' => 'room-end-test', 'status' => 'live',
        ]);

        $body = json_encode(['event' => 'room_finished', 'room' => ['name' => 'room-end-test']]);

        $this->withHeaders(['Authorization' => 'Bearer test', 'Content-Type' => 'application/json'])
            ->call('POST', '/api/webhook/livekit', [], [], [], [], $body)->assertStatus(200);

        expect($room->fresh()->status)->toBe('ended');
    });

    it('queues transcription on recording_finished', function () {
        Queue::fake();

        $client = clientUser(); $provider = freelancerUser();
        $booking = Booking::factory()->create([
            'provider_id' => $provider->id, 'client_id' => $client->id,
            'status' => 'confirmed', 'start_at' => now()->subHour(), 'end_at' => now()->subMinutes(5),
        ]);
        $room = MeetingRoom::factory()->create([
            'booking_id' => $booking->id, 'room_token' => 'room-rec-test', 'status' => 'live',
        ]);

        $body = json_encode([
            'event' => 'recording_finished',
            'room'  => ['name' => 'room-rec-test'],
            'egress'=> ['egress_id' => 'EG_001', 'duration' => 3600, 'size' => 5000000,
                        'file' => ['filename' => 'https://s3.example.com/rec.mp4']],
        ]);

        $this->withHeaders(['Authorization' => 'Bearer test', 'Content-Type' => 'application/json'])
            ->call('POST', '/api/webhook/livekit', [], [], [], [], $body)->assertStatus(200);

        expect(MeetingRecording::where('room_id', $room->id)->exists())->toBeTrue();
        Queue::assertPushed(TranscribeRecording::class);
    });
});
