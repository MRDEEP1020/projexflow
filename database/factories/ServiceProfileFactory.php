<?php

namespace Database\Factories;

use App\Models\ServiceProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceProfileFactory extends Factory
{
    protected $model = ServiceProfile::class;
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'headline'            => $this->faker->jobTitle() . ' · ' . $this->faker->randomElement(['Laravel', 'Vue', 'React', 'Python']),
            'bio'                 => $this->faker->paragraphs(2, true),
            'profession_category' => fake()->randomElement([
                'software_dev',
                'ui_ux',
                'digital_marketing',
                'data_analytics',
                // whatever values your CHECK constraint actually allows
            ]),
            'skills'              => ['Laravel', 'PHP', 'MySQL'],
            'languages'           => ['English', 'French'],
            'location'            => $this->faker->city() . ', ' . $this->faker->country(),
            'hourly_rate'         => $this->faker->randomFloat(2, 15, 100),
            'currency'            => 'USD',
            'availability_status' => 'open_to_work',
            'is_verified'         => false,
            'avg_rating'          => 0,
            'total_reviews'       => 0,
            'response_time_hours' => 24,
            'years_experience'    => $this->faker->numberBetween(1, 10),
            // 'session_duration'    => 60,
        ];
    }
}
