<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextLocalGateway implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.textlocal.api_key', env('TEXTLOCAL_API_KEY'));
        $this->senderId = config('services.textlocal.sender_id', env('TEXTLOCAL_SENDER_ID', 'TXTLCL'));
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->apiKey) {
            Log::warning('TextLocal API key is missing.');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://api.textlocal.in/send/', [
                'apikey' => $this->apiKey,
                'numbers' => $phone,
                'sender' => $this->senderId,
                'message' => $message,
            ]);

            $result = $response->json();
            if (isset($result['status']) && $result['status'] === 'success') {
                return true;
            }

            Log::error('TextLocal SMS failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('TextLocal SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
