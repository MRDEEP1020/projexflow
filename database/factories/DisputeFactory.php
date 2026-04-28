<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/DisputeFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\Dispute;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = Dispute::class;
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'raised_by'   => User::factory(),
            'against'     => User::factory(),
            'reason'      => $this->faker->randomElement([
                'work_not_delivered',
                'quality_issues',
                'scope_creep',
                'non_responsive',
                'other',
            ]),
            'description' => $this->faker->paragraphs(2, true),
            'status'      => 'open',
            'resolution'  => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
