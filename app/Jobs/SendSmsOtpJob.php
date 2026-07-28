<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Sms\SmsGatewayManager;
use Illuminate\Support\Facades\Log;

class SendSmsOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $otp;

    public function __construct($phone, $otp)
    {
        $this->phone = $phone;
        $this->otp = $otp;
    }

    public function handle(): void
    {
        try {
            $message = "Your Blueboxx DA verification code is: {$this->otp}. Valid for 5 minutes.";
            
            $gateway = SmsGatewayManager::resolve();
            $success = $gateway->send($this->phone, $message);
            
            if (!$success) {
                Log::error("Failed to send SMS OTP to {$this->phone} via modular gateway.");
            }
        } catch (\Exception $e) {
            Log::error('SendSmsOtpJob Error: ' . $e->getMessage());
        }
    }
}
