<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send'; // Legacy endpoint, or we can mock/log for easy setup.
    protected ?string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.key');
    }

    /**
     * Send push notification to a user's registered devices.
     */
    public function sendToUser($user, string $title, string $body, array $data = []): bool
    {
        $tokens = DeviceToken::where('user_id', $user->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("No FCM tokens registered for user ID {$user->id}. Push notification skipped.");
            return false;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to multiple FCM device tokens.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        if (empty($tokens)) {
            return false;
        }

        if (!$this->serverKey) {
            Log::warning("FCM server key is not configured. Push Notification logged locally:", [
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
            return true;
        }

        try {
            // Using standard legacy HTTP multicast protocol for simplicity and ease of credentials (just an API key)
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
            ]);

            if ($response->successful()) {
                Log::info("FCM notification sent successfully to " . count($tokens) . " devices.");
                return true;
            }

            Log::error("FCM API failed with response: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}
