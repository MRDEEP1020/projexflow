<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/JobPostFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostFactory extends Factory
{
    protected $model = JobPost::class;
    public function definition(): array
    {
        return [
            'client_id'        => User::factory(),
            'title'            => $this->faker->sentence(6),
            'description'      => $this->faker->paragraphs(3, true),
            'category'         => 'software_dev',
            'type'             => 'fixed',
            'budget_min'       => 500,
            'budget_max'       => 2000,
            'currency'         => 'USD',
            'experience_level' => 'mid',
            'skills_required'  => ['Laravel', 'PHP'],
            'duration'         => '1_month',
            'deadline'         => now()->addDays(14),
            'visibility'       => 'public',
            'max_applicants'   => 20,
            'status'           => 'open',
            'hired_freelancer_id' => null,
        ];
    }
}
