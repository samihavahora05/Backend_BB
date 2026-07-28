<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /**
     * Create an order on the payment gateway.
     *
     * @param string $receiptId A unique receipt or order ID from our system.
     * @param int $amountInPaise Amount in smallest currency unit (e.g. paise/cents).
     * @param string $currency Currency code (e.g. INR, USD).
     * @return array Returns an array with ['order_id' => '...', 'amount' => ...]
     */
    public function createOrder(string $receiptId, int $amountInPaise, string $currency = 'INR'): array;

    /**
     * Verify the payment signature or status after a successful payment.
     *
     * @param array $payload Verification payload (e.g. razorpay_order_id, razorpay_payment_id, razorpay_signature).
     * @return bool True if valid, false otherwise.
     */
    public function verifyPayment(array $payload): bool;
}
