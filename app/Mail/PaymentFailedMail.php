<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Order $order = null,
        public ?string $itemTitle = null,
        public ?string $amount = null,
        public ?string $reason = null,
        public ?string $retryUrl = null
    ) {}

    public function build()
    {
        $itemName = $this->itemTitle ?? ($this->order ? 'your selected program' : 'your checkout');
        
        return $this->subject("Action Required: Complete your enrollment for {$itemName} | Blueboxx DA")
                    ->view('emails.payment_failed', [
                        'user' => $this->user,
                        'order' => $this->order,
                        'itemTitle' => $this->itemTitle,
                        'amount' => $this->amount ?? ($this->order ? $this->order->total_amount : null),
                        'reason' => $this->reason ?? 'Payment authorization was interrupted or cancelled.',
                        'retryUrl' => $this->retryUrl ?? (config('app.frontend_url', 'https://blueboxx.in') . '/checkout'),
                    ]);
    }
}
