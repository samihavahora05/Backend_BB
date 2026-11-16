<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMeetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GoogleAuthController extends Controller
{
    /**
     * 1. Redirect Admin to Google Consent Screen
     * GET /api/auth/google/connect
     */
    public function connect()
    {
        $client = GoogleMeetService::getClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    /**
     * 2. Google OAuth Callback (Captures Refresh Token permanently)
     * GET /api/auth/google/callback
     */
    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code not returned from Google: ' . $request->input('error'),
            ], 400);
        }

        try {
            $client = GoogleMeetService::getClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

            if (isset($token['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching access token: ' . ($token['error_description'] ?? $token['error']),
                ], 400);
            }

            // Store permanent refresh token in storage/app/google/token.json
            $dir = storage_path('app/google');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            File::put($dir . '/token.json', json_encode($token, JSON_PRETTY_PRINT));

            return response()->make("
                <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
                    <h2 style='color: #059669;'>✓ Google Meet Automation Connected Successfully!</h2>
                    <p style='color: #4b5563;'>Your Google Account is now authorized. All future 30+ MCQs candidates will get real, automatically generated Google Meet interview rooms.</p>
                    <p><a href='https://www.blueboxx.in/admin/assessments' style='color: #1B2A6B; font-weight: bold;'>Return to Admin Portal</a></p>
                </div>
            ", 200, ['Content-Type' => 'text/html']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'OAuth Callback Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
