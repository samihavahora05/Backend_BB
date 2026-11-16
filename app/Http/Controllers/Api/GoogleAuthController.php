<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleOAuthToken;
use App\Services\GoogleMeetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * 1. Redirect Organizer (info.blueboxx@gmail.com) to Google OAuth Consent Screen
     * GET /api/auth/google/connect
     */
    public function connect(Request $request)
    {
        $client = GoogleMeetService::getClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    /**
     * 2. Google OAuth Callback - Stores Access & Refresh Token permanently in DB
     * GET /api/auth/google/callback
     */
    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code missing: ' . $request->input('error', 'unknown error'),
            ], 400);
        }

        try {
            $client = GoogleMeetService::getClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

            if (isset($token['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error exchanging code: ' . ($token['error_description'] ?? $token['error']),
                ], 400);
            }

            // Retrieve email from userinfo
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $email = $userInfo->email ?? GoogleMeetService::ORGANIZER_EMAIL;

            // Save permanently in google_oauth_tokens table
            GoogleOAuthToken::updateOrCreate(
                ['email' => $email],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_in' => $token['expires_in'] ?? 3600,
                    'token_type' => $token['token_type'] ?? 'Bearer',
                    'scope' => $token['scope'] ?? GoogleMeetService::REQUIRED_SCOPE,
                    'created_at_timestamp' => now(),
                ]
            );

            Log::info("Google OAuth token successfully stored in DB for: {$email}");

            return response()->make("
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Google Meet Connected</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8fafc; margin: 0; }
                        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 480px; }
                        h2 { color: #059669; margin-top: 0; }
                        p { color: #4b5563; line-height: 1.6; font-size: 14px; }
                        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #1B2A6B; color: white; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class='card'>
                        <h2>✓ Google Meet REST API Authorized!</h2>
                        <p>Connected Account: <strong>{$email}</strong></p>
                        <p>Google Meet REST API (v2 spaces) is now fully authorized to generate genuine Google Meet interview rooms.</p>
                        <a href='https://www.blueboxx.in/admin/assessments'>Return to BlueBoxx Admin Portal</a>
                    </div>
                </body>
                </html>
            ", 200, ['Content-Type' => 'text/html']);
        } catch (\Throwable $e) {
            Log::error("Google OAuth callback exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'OAuth Callback Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. Status check endpoint for Google Meet OAuth connection
     * GET /api/auth/google/status
     */
    public function status()
    {
        $token = GoogleOAuthToken::where('email', GoogleMeetService::ORGANIZER_EMAIL)->latest()->first();
        $isConnected = !empty($token?->access_token) && !empty($token?->refresh_token);

        return response()->json([
            'success' => true,
            'is_connected' => $isConnected,
            'organizer_email' => GoogleMeetService::ORGANIZER_EMAIL,
            'scope' => $token?->scope,
            'updated_at' => $token?->updated_at?->toISOString(),
        ]);
    }
}
