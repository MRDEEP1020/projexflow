<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/ReviewFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;
    public function definition(): array
    {
        return [
            'reviewer_id' => User::factory(),
            'reviewee_id' => User::factory(),
            'booking_id'  => null,
            'project_id'  => null,
            'rating'      => $this->faker->numberBetween(1, 5),
            'body'        => $this->faker->paragraph(),
            'is_verified' => true,
        ];
    }
}
