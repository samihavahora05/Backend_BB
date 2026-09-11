<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?Payment $payment = null,
        public ?string $itemTitle = null
    ) {}

    public function build()
    {
        $orderNumber = $this->order->order_number ?? ('ORD-' . $this->order->id);
        
        return $this->subject("Payment Confirmed: Order #{$orderNumber} | Blueboxx DA")
                    ->view('emails.payment_success', [
                        'order' => $this->order,
                        'payment' => $this->payment,
                        'itemTitle' => $this->itemTitle,
                        'user' => $this->order->user,
                    ]);
    }
}
