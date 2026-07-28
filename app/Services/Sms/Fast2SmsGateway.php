<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Fast2SmsGateway implements SmsGatewayInterface
{
    protected string $authKey;

    public function __construct()
    {
        $this->authKey = config('services.fast2sms.auth_key', env('FAST2SMS_AUTH_KEY'));
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->authKey) {
            Log::warning('Fast2SMS auth key is missing.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'authorization' => $this->authKey
            ])->post('https://www.fast2sms.com/dev/bulkV2', [
                'route' => 'v3',
                'sender_id' => 'TXTIND', // Default testing sender ID
                'message' => $message,
                'language' => 'english',
                'flash' => 0,
                'numbers' => $phone,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Fast2SMS SMS failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Fast2SMS SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
