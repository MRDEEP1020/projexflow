<?php
namespace Database\Factories;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;
    public function definition(): array
    {
        return [
            'job_post_id'   => JobPost::factory(),
            'freelancer_id' => User::factory(),
            'cover_letter'  => $this->faker->paragraphs(2, true),
            'proposed_rate' => $this->faker->randomFloat(2, 20, 100),
            'availability'  => 'within_1_week',
            'status'        => 'pending',
        ];
    }
}
