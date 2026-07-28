<?php

namespace App\Services\Sms;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioGateway implements SmsGatewayInterface
{
    protected string $sid;
    protected string $token;
    protected string $from;

    public function __construct()
    {
        $this->sid = config('services.twilio.sid', env('TWILIO_SID'));
        $this->token = config('services.twilio.token', env('TWILIO_AUTH_TOKEN'));
        $this->from = config('services.twilio.from', env('TWILIO_PHONE_NUMBER'));
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->sid || !$this->token) {
            Log::warning('Twilio credentials are not set.');
            return false;
        }

        try {
            $twilio = new Client($this->sid, $this->token);
            $twilio->messages->create($phone, [
                'from' => $this->from,
                'body' => $message
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
