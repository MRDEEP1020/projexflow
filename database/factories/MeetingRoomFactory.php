<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/MeetingRoomFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\MeetingRoom;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingRoomFactory extends Factory
{
    protected $model = MeetingRoom::class;
    public function definition(): array
    {
        return [
            'booking_id'   => Booking::factory(),
            'created_by'   => User::factory(),
            'title'        => 'Meeting Room',
            'room_token'   => 'room-' . Str::random(32),
            'status'       => 'scheduled',
            'started_at'   => null,
            'ended_at'     => null,
        ];
    }
}
