<?php
// database/factories/ProjectFactory.php
namespace Database\Factories;

use App\Models\Project;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'name'                 => $this->faker->bs(),
            'description'          => $this->faker->paragraph(),
            'status'               => 'active',
            'created_by' => \App\Models\User::factory(), // add this

            'priority'             => $this->faker->randomElement(['low', 'medium', 'high']),
            'start_date'           => now()->subDays(10),
            'due_date'             => now()->addDays(30),
            'github_repo'          => null,
            'github_branch'        => 'main',
            'client_portal_enabled' => false,
            'client_token'         => Str::random(64),
            'archived_at'          => null,
        ];
    }
}
