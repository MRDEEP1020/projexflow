<?php
namespace Database\Factories;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;
    public function definition(): array
    {
        $total      = $this->faker->randomFloat(2, 200, 5000);
        $depositPct = 30;
        $feePct     = 10;
        return [
            'freelancer_id'           => User::factory(),
            'client_id'               => User::factory(),
            'title'                   => $this->faker->bs() . ' Project',
            'description'             => $this->faker->paragraph(),
            'total_amount'            => $total,
            'deposit_percentage'      => $depositPct,
            'deposit_amount'          => round($total * $depositPct / 100, 2),
            'platform_fee_percentage' => $feePct,
            'platform_fee_amount'     => round($total * $feePct / 100, 2),
            'currency'                => 'USD',
            'status'                  => 'draft',
            'auto_release_at'         => null,
            'auto_released'           => false,
            'work_submitted_at'       => null,
            'completed_at'            => null,
            'disputed_at'             => null,
        ];
    }
}
