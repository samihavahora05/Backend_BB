<?php

namespace App\Services\Payments;

use App\Models\SystemApiCredential;
use Illuminate\Support\Facades\Http;
use Exception;

class RazorpayGatewayService implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        // Dynamically fetch from the database
        $credentials = SystemApiCredential::where('provider', 'razorpay')->where('status', 'active')->first();
        
        if (!$credentials) {
            throw new Exception("Razorpay credentials are not configured or inactive in the Admin Panel.");
        }

        $this->apiKey = $credentials->api_key;
        $this->apiSecret = $credentials->api_secret;
    }

    public function createOrder(string $receiptId, int $amountInPaise, string $currency = 'INR'): array
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'receipt' => $receiptId,
                'amount' => $amountInPaise,
                'currency' => $currency,
            ]);

        if ($response->failed()) {
            throw new Exception('Razorpay Error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'order_id' => $data['id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? null;
        $paymentId = $payload['razorpay_payment_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;

        if (!$orderId || !$paymentId || !$signature) {
            return false;
        }

        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->apiSecret);

        // hash_equals prevents timing attacks
        return hash_equals($generatedSignature, $signature);
    }
}
