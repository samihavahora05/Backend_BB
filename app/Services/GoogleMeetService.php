<?php

namespace App\Services;

use App\Models\GoogleOAuthToken;
use App\Models\GoogleMeetInterview;
use App\Models\AssessmentAttempt;
use Google_Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMeetService
{
    const ORGANIZER_EMAIL = 'info.blueboxx@gmail.com';
    const REQUIRED_SCOPE = 'https://www.googleapis.com/auth/meetings.space.created';
    const MEET_REST_API = 'https://meet.googleapis.com/v2/spaces';

    /**
     * Get Google_Client configured for Google Meet REST API OAuth 2.0 flow
     */
    public static function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('BlueBoxx Google Meet Integration');
        
        // Use the official Google Meet scope required
        $client->setScopes([
            self::REQUIRED_SCOPE,
            'https://www.googleapis.com/auth/userinfo.email',
            'openid',
        ]);
        
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force refresh_token on connect
        $client->setIncludeGrantedScopes(true);

        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        $redirectUri = env('GOOGLE_REDIRECT_URI', 'https://backend.blueboxx.in/api/auth/google/callback');

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        return $client;
    }

    /**
     * Retrieve valid Access Token, automatically refreshing with refresh_token if expired
     */
    public static function getValidAccessToken(): ?string
    {
        $tokenRecord = GoogleOAuthToken::where('email', self::ORGANIZER_EMAIL)->latest()->first();

        if (!$tokenRecord || empty($tokenRecord->access_token)) {
            Log::warning("Google Meet: No OAuth token stored for organizer " . self::ORGANIZER_EMAIL);
            return null;
        }

        $createdAt = $tokenRecord->created_at_timestamp ? $tokenRecord->created_at_timestamp->timestamp : $tokenRecord->updated_at->timestamp;
        $expiresIn = $tokenRecord->expires_in ?: 3500;
        $isExpired = (time() >= ($createdAt + $expiresIn - 120)); // Buffer 2 minutes

        if (!$isExpired) {
            return $tokenRecord->access_token;
        }

        // Refresh token if expired
        if (empty($tokenRecord->refresh_token)) {
            Log::warning("Google Meet: Token expired and no refresh_token found for " . self::ORGANIZER_EMAIL);
            return null;
        }

        try {
            $client = self::getClient();
            $newToken = $client->fetchAccessTokenWithRefreshToken($tokenRecord->refresh_token);

            if (isset($newToken['error'])) {
                Log::error("Google Meet token refresh failed: " . json_encode($newToken));
                return null;
            }

            $tokenRecord->update([
                'access_token' => $newToken['access_token'],
                'expires_in' => $newToken['expires_in'] ?? 3600,
                'created_at_timestamp' => now(),
            ]);

            Log::info("Google Meet access token refreshed successfully for " . self::ORGANIZER_EMAIL);
            return $newToken['access_token'];
        } catch (\Throwable $e) {
            Log::error("Google Meet refresh exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create actual Google Meet Space via official Google Meet REST API v2
     * POST https://meet.googleapis.com/v2/spaces
     * Returns array with meeting_uri, meeting_code, google_space_name, space_details
     */
    public static function createMeetingSpace(?AssessmentAttempt $attempt = null): ?array
    {
        $accessToken = self::getValidAccessToken();

        if (!$accessToken) {
            Log::error("Google Meet API abort: Organizer " . self::ORGANIZER_EMAIL . " is not connected or token is invalid.");
            return null;
        }

        try {
            // Official Google Meet v2 spaces endpoint
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::MEET_REST_API, [
                    'config' => [
                        'accessType' => 'OPEN', // OPEN / TRUSTED
                    ],
                ]);

            if (!$response->successful()) {
                Log::error("Google Meet REST API v2 error [{$response->status()}]: " . $response->body());
                return null;
            }

            $data = $response->json();
            /*
             * Response schema from POST https://meet.googleapis.com/v2/spaces:
             * {
             *   "name": "spaces/123456789",
             *   "meetingUri": "https://meet.google.com/xxx-yyyy-zzz",
             *   "meetingCode": "xxx-yyyy-zzz",
             *   "config": { ... },
             *   "activeConference": { ... }
             * }
             */
            $meetingUri = $data['meetingUri'] ?? null;
            $meetingCode = $data['meetingCode'] ?? null;
            $spaceName = $data['name'] ?? null;

            if (empty($meetingUri)) {
                Log::error("Google Meet REST API returned no meetingUri: " . json_encode($data));
                return null;
            }

            // Save record in google_meet_interviews table
            $interviewRecord = GoogleMeetInterview::create([
                'attempt_id' => $attempt?->id,
                'user_id' => $attempt?->user_id,
                'google_space_name' => $spaceName,
                'meeting_code' => $meetingCode,
                'meeting_uri' => $meetingUri,
                'organizer_email' => self::ORGANIZER_EMAIL,
                'meeting_status' => 'scheduled',
                'scheduled_at' => now()->addDay()->setTime(11, 0, 0),
                'space_details' => $data,
            ]);

            Log::info("REAL Google Meet Space successfully created: {$meetingUri} (Code: {$meetingCode})");

            return [
                'meeting_uri' => $meetingUri,
                'meeting_code' => $meetingCode,
                'space_name' => $spaceName,
                'space_details' => $data,
                'interview_id' => $interviewRecord->id,
            ];
        } catch (\Throwable $e) {
            Log::error("Google Meet Space generation exception: " . $e->getMessage());
            return null;
        }
    }
}
