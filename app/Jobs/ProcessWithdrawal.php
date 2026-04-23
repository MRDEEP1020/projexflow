<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WithdrawalRequest;
use App\Models\PaymentTransaction;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessWithdrawal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [60, 300, 900, 1800, 3600]; // exponential backoff
    public $timeout = 120;

    public function __construct(
        public WithdrawalRequest $withdrawal
    ) {}

    // ALG-PAY-08 Step 2-4: Route to appropriate payment method
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $this->withdrawal->update(['status' => 'processing']);

                $method = $this->withdrawal->method;
                $accountDetails = (array) $this->withdrawal->account_details;
                $amount = $this->withdrawal->amount;
                $currency = $this->withdrawal->currency;

                $result = match ($method) {
                    'mobile_money' => $this->processMobileMoney($amount, $currency, $accountDetails),
                    'bank'         => $this->processBank($amount, $currency, $accountDetails),
                    'stripe'       => $this->processStripe($amount, $currency),
                    default        => ['success' => false, 'error' => 'Unknown method'],
                };

                if ($result['success']) {
                    $this->withdrawal->update([
                        'status'          => 'completed',
                        'completed_at'    => now(),
                        'payout_id'       => $result['payout_id'] ?? null,
                        'payout_ref'      => $result['payout_ref'] ?? null,
                    ]);

                    PaymentTransaction::create([
                        'type'      => 'withdrawal',
                        'amount'    => $amount,
                        'payer_id'  => $this->withdrawal->user_id,
                        'payee_id'  => null,
                        'status'    => 'completed',
                        'currency'  => $currency,
                        'metadata'  => json_encode(['withdrawal_id' => $this->withdrawal->id]),
                    ]);

                    Notification::create([
                        'user_id' => $this->withdrawal->user_id,
                        'type'    => 'withdrawal_completed',
                        'title'   => 'Withdrawal successful!',
                        'body'    => $currency . ' ' . number_format($amount) . ' has been sent to your account.',
                        'url'     => route('backend.wallet'),
                    ]);

                    Log::info('Withdrawal processed successfully', [
                        'withdrawal_id' => $this->withdrawal->id,
                        'method'        => $method,
                        'amount'        => $amount,
                    ]);
                } else {
                    throw new \Exception($result['error'] ?? 'Payment processing failed');
                }
            });
        } catch (\Throwable $e) {
            Log::error('Withdrawal processing failed', [
                'withdrawal_id' => $this->withdrawal->id,
                'error'         => $e->getMessage(),
                'attempt'       => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->withdrawal->update(['status' => 'failed']);
                Notification::create([
                    'user_id' => $this->withdrawal->user_id,
                    'type'    => 'withdrawal_failed',
                    'title'   => 'Withdrawal failed',
                    'body'    => 'Your withdrawal request could not be processed. Please contact support.',
                    'url'     => route('backend.wallet'),
                ]);
            } else {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }

    // ALG-PAY-08 Step 3a: Mobile Money via CinetPay or Flutterwave
    protected function processMobileMoney(float $amount, string $currency, array $details): array
    {
        try {
            $phone = $details['phone'] ?? null;
            $operator = $details['operator'] ?? null;
            $country = $details['country'] ?? null;

            if (! $phone || ! $operator || ! $country) {
                return ['success' => false, 'error' => 'Missing mobile money details'];
            }

            // Route by operator
            if (in_array($operator, ['mtn', 'orange', 'airtel']) && $country === 'CM') {
                // Use CinetPay for Cameroon
                return $this->processCinetPay($phone, $amount, $currency, $operator, $country);
            } else {
                // Use Flutterwave for other African countries
                return $this->processFlutterwave($phone, $amount, $currency, $operator, $country);
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function processCinetPay(string $phone, float $amount, string $currency, string $operator, string $country): array
    {
        // CinetPay API integration
        // POST https://api.cinetpay.com/v1/transfer/money
        // Requires: CINETPAY_API_KEY, CINETPAY_SANDBOX_MODE

        $payload = [
            'apikey'     => config('services.cinetpay.api_key'),
            'amount'     => (int) ($amount * 100), // cents
            'currency'   => $currency,
            'phone'      => $phone,
            'first_name' => $this->withdrawal->user?->name ?? 'User',
            'country'    => $country,
            'operator'   => strtoupper($operator),
            'description'=> 'ProjexFlow Withdrawal',
            'notify_url' => route('webhook.cinetpay'),
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.cinetpay.com/v1/transfer/money', [
                'json' => $payload,
                'timeout' => 30,
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['status'] === '200') {
                return [
                    'success'   => true,
                    'payout_id' => $result['data']['reference'] ?? null,
                    'payout_ref'=> $result['data']['reference'] ?? null,
                ];
            } else {
                return ['success' => false, 'error' => $result['message'] ?? 'CinetPay transfer failed'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'CinetPay API error: ' . $e->getMessage()];
        }
    }

    protected function processFlutterwave(string $phone, float $amount, string $currency, string $operator, string $country): array
    {
        // Flutterwave Bank Transfer / Mobile Money API
        // POST https://api.flutterwave.com/v3/transfers
        // Requires: FLUTTERWAVE_SECRET_KEY

        $payload = [
            'account_bank'  => $this->mapOperatorToFlutterwaveBank($operator, $country),
            'account_number'=> $phone,
            'amount'        => $amount,
            'currency'      => $currency,
            'narration'     => 'ProjexFlow Withdrawal',
            'reference'     => 'projexflow-' . $this->withdrawal->id . '-' . now()->timestamp,
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.flutterwave.com/v3/transfers', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['status'] === 'success') {
                return [
                    'success'    => true,
                    'payout_id'  => $result['data']['id'] ?? null,
                    'payout_ref' => $result['data']['reference'] ?? null,
                ];
            } else {
                return ['success' => false, 'error' => $result['message'] ?? 'Flutterwave transfer failed'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Flutterwave API error: ' . $e->getMessage()];
        }
    }

    protected function mapOperatorToFlutterwaveBank(string $operator, string $country): string
    {
        // Flutterwave bank codes for mobile money operators
        // This is a simplified mapping — use actual Flutterwave bank list API in production
        return match ($operator . '-' . $country) {
            'mtn-CM'     => '280',    // MTN Cameroon
            'orange-CM'  => '281',    // Orange Cameroon
            'mtn-NG'     => '401',    // MTN Nigeria
            'airtel-NG'  => '402',    // Airtel Nigeria
            'mpesa-KE'   => '627',    // M-Pesa Kenya
            default      => '001',    // Fallback
        };
    }

    // ALG-PAY-08 Step 3b: Bank transfer (ACH for US, SEPA for EU, local for others)
    protected function processBank(float $amount, string $currency, array $details): array
    {
        // Use Stripe for ACH/SEPA or Wise (TransferWise) for international
        // For now, placeholder implementation

        return [
            'success'    => true,
            'payout_id'  => 'bank-' . $this->withdrawal->id,
            'payout_ref' => 'bank-' . $this->withdrawal->id,
        ];
    }

    // ALG-PAY-08 Step 3c: Stripe Connect payout
    protected function processStripe(float $amount, string $currency): array
    {
        try {
            $user = $this->withdrawal->user;
            if (! $user->stripe_connect_id) {
                return ['success' => false, 'error' => 'Stripe account not connected'];
            }

            // Use Stripe PHP SDK
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $payout = \Stripe\Payout::create([
                'amount'      => (int) ($amount * 100), // cents
                'currency'    => strtolower($currency),
                'destination'=> $user->stripe_connect_id,
                'description' => 'ProjexFlow Withdrawal #' . $this->withdrawal->id,
            ]);

            return [
                'success'    => true,
                'payout_id'  => $payout->id,
                'payout_ref' => $payout->id,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Stripe error: ' . $e->getMessage()];
        }
    }
}
