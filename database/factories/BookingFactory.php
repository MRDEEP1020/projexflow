<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/BookingFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+30 days');
        return [
            'provider_id'  => User::factory(),
            'client_id'    => User::factory(),
            'title'        => 'Consultation Session',
            'client_name'  => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'message'      => $this->faker->sentence(),
            'start_at'     => $start,
            'end_at'       => (clone $start)->modify('+1 hour'),
            'status'       => 'pending',
            'meeting_room_id' => null,
        ];
    }
}
