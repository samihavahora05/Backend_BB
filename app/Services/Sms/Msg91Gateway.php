<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Gateway implements SmsGatewayInterface
{
    protected string $authKey;
    protected string $senderId;
    protected string $route;

    public function __construct()
    {
        $this->authKey = config('services.msg91.auth_key', env('MSG91_AUTH_KEY')) ?? '';
        $this->senderId = config('services.msg91.sender_id', env('MSG91_SENDER_ID', 'BLUBOX')) ?? 'BLUBOX';
        $this->route = config('services.msg91.route', env('MSG91_ROUTE', '4')) ?? '4'; // 4 for transactional
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->authKey) {
            Log::warning('MSG91 auth key is missing.');
            return false;
        }

        try {
            $response = Http::get('https://api.msg91.com/api/sendhttp.php', [
                'authkey' => $this->authKey,
                'mobiles' => $phone,
                'message' => $message,
                'sender' => $this->senderId,
                'route' => $this->route,
                'country' => '91' // Assuming India for BlueBoxx DA, can be parameterized
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('MSG91 SMS failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('MSG91 SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
