<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MeetingRecording;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Client;

class TranscribeRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [600, 3600, 7200]; // 10min, 1h, 2h
    public $timeout = 600; // 10 minutes

    public function __construct(
        public MeetingRecording $recording
    ) {}

    public function handle(): void
    {
        try {
            $recording = $this->recording;

            // Step 1: Download recording from LiveKit storage (S3/GCS)
            $audioData = $this->downloadRecording($recording->recording_url);
            if (! $audioData) {
                throw new \Exception('Failed to download recording');
            }

            // Step 2: Send to OpenAI Whisper API
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->audio()->transcribe(
                model: 'whisper-1',
                file: \GuzzleHttp\Psr7\stream_for($audioData),
                responseFormat: 'json',
                temperature: 0.0,
                language: 'en', // Can auto-detect if omitted
            );

            $transcript = $response->text;

            // Step 3: Store transcript (as S3 object or in DB)
            $transcriptUrl = $this->storeTranscript($recording, $transcript);

            // Step 4: Update recording record
            $recording->update([
                'transcript_text'  => $transcript,
                'transcript_url'   => $transcriptUrl,
                'is_processing'    => false,
                'processed_at'     => now(),
            ]);

            Log::info('Recording transcribed successfully', [
                'recording_id' => $recording->id,
                'length'       => strlen($transcript),
            ]);

            // Notify room participants that transcript is ready
            $booking = $recording->room->booking;
            if ($booking) {
                foreach ([$booking->provider_id, $booking->client_id] as $userId) {
                    if ($userId) {
                        \App\Models\Notification::create([
                            'user_id' => $userId,
                            'type'    => 'transcript_ready',
                            'title'   => 'Meeting transcript available',
                            'body'    => 'Your meeting transcript has been transcribed.',
                            'url'     => route('backend.bookingInbox'),
                        ]);
                    }
                }
            }

        } catch (\Throwable $e) {
            Log::error('Transcription failed', [
                'recording_id' => $this->recording->id,
                'error'        => $e->getMessage(),
                'attempt'      => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->recording->update(['is_processing' => false]);
            } else {
                $this->release($this->backoff[$this->attempts() - 1] ?? 600);
            }
        }
    }

    protected function downloadRecording(string $url): ?string
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url, ['timeout' => 120]);
            return (string) $response->getBody();
        } catch (\Throwable $e) {
            Log::error('Failed to download recording', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function storeTranscript(MeetingRecording $recording, string $transcript): string
    {
        // Option 1: Store in S3
        $filename = 'transcripts/recording-' . $recording->id . '-' . now()->timestamp . '.txt';
        Storage::disk('s3')->put($filename, $transcript, ['visibility' => 'private']);
        
        return Storage::disk('s3')->url($filename);

        // Option 2: Store in database only (no file storage)
        // return null; // transcript stored in DB as transcript_text
    }
}
