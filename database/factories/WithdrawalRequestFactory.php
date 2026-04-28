<?php
// ══════════════════════════════════════════════════════════════════
// database/factories/WithdrawalRequestFactory.php
// ══════════════════════════════════════════════════════════════════
namespace Database\Factories;
use App\Models\WithdrawalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalRequestFactory extends Factory
{
    protected $model = WithdrawalRequest::class;
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'amount'          => $this->faker->randomFloat(2, 50, 1000),
            'currency'        => 'USD',
            'method'          => 'mobile_money',
            'account_details' => ['phone' => '+237600000000', 'operator' => 'mtn', 'country' => 'CM'],
            'status'          => 'pending',
            'payout_id'       => null,
            'payout_ref'      => null,
            'failure_reason'  => null,
            'completed_at'    => null,
        ];
    }
}
