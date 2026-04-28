<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/TaskFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;
    public function definition(): array
    {
        return [
            'project_id'      => Project::factory(),
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(),
            'status'          => 'planning',
            'priority'        => 'medium',
            'assigned_to'     => null,
            'due_date'        => now()->addDays(7),
            'completed_at'    => null,
            'deliverable_type'=> null,
            'deliverable_url' => null,
            'deliverable_note'=> null,
            'parent_task_id'  => null,
            'milestone_id'    => null,
            'position'        => 0,
        ];
    }
}
