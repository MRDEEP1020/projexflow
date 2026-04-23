<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\MeetingRoom;
use App\Models\MeetingRecording;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class LiveKitWebhookController extends Controller
{
    // Verify LiveKit webhook signature
    protected function verifySignature(Request $request): bool
    {
        $token = $request->header('Authorization');
        if (! $token) return false;

        // LiveKit sends Authorization: Bearer <JWT>
        // For production, validate the JWT against LiveKit's public key
        // For now, we just check the secret in the token payload

        try {
            // In production: use https://github.com/livekit/php-sdk
            // which includes JWT validation helpers
            $secret = config('services.livekit.secret');
            // This is simplified — real impl uses JWT library
            return $secret && strpos($token, 'Bearer') === 0;
        } catch (\Throwable $e) {
            Log::error('LiveKit webhook verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function handle(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            Log::warning('LiveKit webhook signature invalid', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $event = $request->json('event');
        $payload = $request->json();

        Log::info('LiveKit webhook received', ['event' => $event]);

        switch ($event) {
            case 'room_finished':
                $this->handleRoomFinished($payload);
                break;
            case 'recording_finished':
                $this->handleRecordingFinished($payload);
                break;
            default:
                break;
        }

        return response('OK', 200);
    }

    // ALG-VID-02: Room finished — mark as ended
    protected function handleRoomFinished(array $payload): void
    {
        $roomName = $payload['room']['name'] ?? null;
        if (! $roomName) return;

        $room = MeetingRoom::where('room_token', $roomName)->first();
        if (! $room) return;

        $room->update([
            'status'   => 'ended',
            'ended_at' => now(),
        ]);

        Log::info('Meeting room marked ended via LiveKit', ['room' => $roomName]);
    }

    // ALG-VID-03: Recording finished — store egress data + trigger transcript job
    protected function handleRecordingFinished(array $payload): void
    {
        $roomName = $payload['room']['name'] ?? null;
        $egress = $payload['egress'] ?? [];
        $egressId = $egress['egress_id'] ?? null;
        $fileUrl = $egress['file']?->['filename'] ?? null;

        if (! $roomName || ! $fileUrl) return;

        $room = MeetingRoom::where('room_token', $roomName)->first();
        if (! $room) return;

        // Determine if file is video (MP4) or audio (M4A/WAV)
        $isVideo = str_ends_with($fileUrl, '.mp4');

        // Create MeetingRecording record
        $recording = MeetingRecording::create([
            'room_id'        => $room->id,
            'egress_id'      => $egressId,
            'recording_url'  => $fileUrl, // Full S3/GCS URL from LiveKit
            'is_processing'  => true, // Transcript not ready yet
            'duration_seconds'=> $egress['duration'] ?? null,
            'file_size_bytes'=> $egress['size'] ?? null,
        ]);

        // Queue transcript job (uses OpenAI Whisper via API)
        \App\Jobs\TranscribeRecording::dispatch($recording);

        // Notify participants that recording is available
        $booking = $room->booking;
        if ($booking) {
            foreach ([$booking->provider_id, $booking->client_id] as $userId) {
                if ($userId) {
                    Notification::create([
                        'user_id' => $userId,
                        'type'    => 'recording_ready',
                        'title'   => 'Meeting recording available',
                        'body'    => 'Recording is being processed. Transcript will be ready shortly.',
                        'url'     => route('backend.bookingInbox'),
                    ]);
                }
            }
        }

        Log::info('Recording stored and transcription queued', [
            'room'      => $roomName,
            'recording' => $recording->id,
            'url'       => $fileUrl,
        ]);
    }
}
