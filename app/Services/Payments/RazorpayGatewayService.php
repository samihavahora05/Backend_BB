<?php

namespace App\Services\Payments;

use App\Models\SystemApiCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayGatewayService implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_api_credentials')) {
                $credentials = SystemApiCredential::where('provider', 'razorpay')->where('status', 'active')->first();
                if ($credentials && !empty($credentials->api_key) && !empty($credentials->api_secret)) {
                    $this->apiKey = $credentials->api_key;
                    $this->apiSecret = $credentials->api_secret;
                    return;
                }
            }
        } catch (\Throwable $e) {}

        $this->apiKey = config('services.razorpay.key') ?? env('RAZORPAY_KEY_ID', '');
        $this->apiSecret = config('services.razorpay.secret') ?? env('RAZORPAY_KEY_SECRET', '');
    }

    public function createOrder(string $receiptId, int $amountInPaise, string $currency = 'INR'): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new Exception("Razorpay API credentials (RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET) are missing in environment configuration.");
        }

        if ($this->apiKey === 'rzp_test_mockkey' || str_starts_with($this->apiKey, 'rzp_test_mock')) {
            throw new Exception("Razorpay API Key is set to placeholder ('rzp_test_mockkey'). Please configure a valid Razorpay Test Mode Key (e.g., rzp_test_...) in your backend .env file.");
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'receipt' => $receiptId,
                    'amount' => $amountInPaise,
                    'currency' => $currency,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'order_id' => $data['id'],
                    'amount'   => $data['amount'],
                    'currency' => $data['currency'],
                ];
            }

            $errorDesc = $response->json()['error']['description'] ?? $response->body();
            Log::error("Razorpay API Order Creation Error: " . $errorDesc);
            throw new Exception("Razorpay API Error: " . $errorDesc);
        } catch (\Exception $e) {
            Log::error("Razorpay API Exception: " . $e->getMessage());
            throw $e;
        }
    }

    public function verifyPayment(array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? null;
        $paymentId = $payload['razorpay_payment_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;

        if (!$orderId || !$paymentId || !$signature) {
            return false;
        }

        if (empty($this->apiSecret)) {
            return false;
        }

        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->apiSecret);

        return hash_equals($generatedSignature, $signature);
    }
}
