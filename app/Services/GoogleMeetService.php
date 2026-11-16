<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_ConferenceSolutionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleMeetService
{
    /**
     * Get configured Google Client
     */
    public static function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('BlueBoxx Portal');
        $client->setScopes([
            Google_Service_Calendar::CALENDAR_EVENTS,
            Google_Service_Calendar::CALENDAR,
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Check for json file path or credentials in storage / root
        $jsonPath = base_path('../client_secret_google.json');
        if (!file_exists($jsonPath)) {
            $jsonPath = storage_path('app/google/client_secret.json');
        }

        if (file_exists($jsonPath)) {
            $client->setAuthConfig($jsonPath);
        } else {
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(env('GOOGLE_REDIRECT_URI', 'https://backend.blueboxx.in/api/auth/google/callback'));
        }

        // Check if access/refresh token is saved
        $tokenPath = storage_path('app/google/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                }
            }
        }

        return $client;
    }

    /**
     * Create live Google Meet via Calendar API, or fallback to configured active room
     */
    public static function createMeeting(string $summary, string $description, $startTime, $endTime = null, array $attendeeEmails = []): ?string
    {
        // 1. Check if an official permanent Google Meet link is configured in .env
        $configuredRoom = env('DEFAULT_INTERVIEW_MEET_LINK');

        try {
            $client = self::getClient();

            // If token exists and authenticated, create real Google Calendar Event with Google Meet conference
            if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
                $service = new Google_Service_Calendar($client);

                $event = new Google_Service_Calendar_Event([
                    'summary' => $summary,
                    'description' => $description,
                    'start' => new Google_Service_Calendar_EventDateTime([
                        'dateTime' => $startTime->toRfc3339String(),
                        'timeZone' => config('app.timezone', 'Asia/Kolkata'),
                    ]),
                    'end' => new Google_Service_Calendar_EventDateTime([
                        'dateTime' => ($endTime ?? $startTime->copy()->addMinutes(45))->toRfc3339String(),
                        'timeZone' => config('app.timezone', 'Asia/Kolkata'),
                    ]),
                ]);

                // Attach Google Meet conference
                $conferenceRequest = new Google_Service_Calendar_CreateConferenceRequest();
                $conferenceRequest->setRequestId('bb-meet-' . Str::uuid()->toString());
                $solutionKey = new Google_Service_Calendar_ConferenceSolutionKey();
                $solutionKey->setType('hangoutsMeet');
                $conferenceRequest->setConferenceSolutionKey($solutionKey);

                $conferenceData = new Google_Service_Calendar_ConferenceData();
                $conferenceData->setCreateRequest($conferenceRequest);
                $event->setConferenceData($conferenceData);

                // Add attendees
                if (!empty($attendeeEmails)) {
                    $attendees = [];
                    foreach ($attendeeEmails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $attendees[] = ['email' => $email];
                        }
                    }
                    $event->setAttendees($attendees);
                }

                $createdEvent = $service->events->insert('primary', $event, [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => 'all',
                ]);

                $meetLink = $createdEvent->getHangoutLink();
                if (!empty($meetLink)) {
                    Log::info("Google Meet created successfully via Calendar API: {$meetLink}");
                    return $meetLink;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Google Calendar API Event Creation note: " . $e->getMessage());
        }

        // 2. Return configured permanent room if available
        if (!empty($configuredRoom)) {
            return $configuredRoom;
        }

        // 3. Fallback: Google Meet launch room with direct meeting identifier
        return 'https://meet.google.com/new';
    }
}
