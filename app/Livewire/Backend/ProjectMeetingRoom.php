<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\MeetingRoom;
use App\Models\MeetingParticipant;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Meeting Room')]
class ProjectMeetingRoom extends Component
{
    public string      $roomToken  = '';
    public MeetingRoom $room;
    public string      $livekitJwt = '';
    public bool        $isHost     = false;
    public bool        $ended      = false;

    public function mount(string $token): void
    {
        $room = MeetingRoom::where('room_token', $token)->firstOrFail();

        // Authorization: must be booking participant or project member
        $booking   = $room->booking;
        $isParty   = $booking && (
            $booking->provider_id === Auth::id()
            || $booking->client_id === Auth::id()
        );

        abort_unless($isParty, 403, 'You are not a participant in this meeting.');

        $this->room      = $room;
        $this->roomToken = $token;
        $this->isHost    = $booking->provider_id === Auth::id();
        $this->ended     = $room->status === 'ended';

        // Mark room as live on first join
        if ($room->status === 'scheduled') {
            $room->update(['status' => 'live', 'started_at' => now()]);
        }

        // Record participant
        MeetingParticipant::updateOrCreate(
            ['room_id' => $room->id, 'user_id' => Auth::id()],
            ['role' => $this->isHost ? 'host' : 'participant', 'joined_at' => now()]
        );

        // Generate LiveKit JWT
        $this->livekitJwt = $this->generateLivekitToken($room);
    }

    protected function generateLivekitToken(MeetingRoom $room): string
    {
        // LiveKit AccessToken generation
        // Requires: composer require agence104/livekit-server-sdk
        // If SDK not installed, returns a placeholder — swap with real implementation

        try {
            $apiKey    = config('services.livekit.key');
            $apiSecret = config('services.livekit.secret');

            if (! $apiKey || ! $apiSecret) {
                return 'livekit-jwt-placeholder-configure-env';
            }

            // Using agence104/livekit-server-sdk
            $tokenOptions = (new \Agence104\LiveKit\AccessTokenOptions())
                ->setIdentity((string) Auth::id())
                ->setName(Auth::user()->name)
                ->setTtl(7200); // 2 hours

            $grants = new \Agence104\LiveKit\VideoGrant();
            $grants->setRoomJoin(true)
                   ->setRoom($room->room_token)
                   ->setCanPublish(true)
                   ->setCanSubscribe(true)
                   ->setCanPublishData(true);

            if ($this->isHost) {
                $grants->setRoomAdmin(true);
            }

            $token = new \Agence104\LiveKit\AccessToken($apiKey, $apiSecret, $tokenOptions);
            $token->setGrant($grants);

            return $token->toJwt();

        } catch (\Throwable $e) {
            // Log and return placeholder so the page still renders
            Log::error('LiveKit JWT error: ' . $e->getMessage());
            return 'livekit-jwt-error-check-logs';
        }
    }

    public function render()
    {
        return view('livewire.backend.meetingRoom');
    }
}
