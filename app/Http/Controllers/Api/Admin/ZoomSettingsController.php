<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSetting;
use Illuminate\Http\Request;

class ZoomSettingsController extends Controller
{
    /**
     * Get the current Zoom settings (singleton row).
     */
    public function show()
    {
        $settings = ZoomSetting::getSettings();

        // Mask the client secret for display
        $display = $settings->toArray();
        if (!empty($display['client_secret'])) {
            $display['client_secret_masked'] = str_repeat('*', 8) . substr($display['client_secret'], -4);
            $display['has_credentials']      = true;
        } else {
            $display['has_credentials'] = false;
        }

        return response()->json(['success' => true, 'data' => $display]);
    }

    /**
     * Update Zoom settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'account_id'          => 'nullable|string|max:255',
            'client_id'           => 'nullable|string|max:255',
            'client_secret'       => 'nullable|string|max:255',
            'auto_recording'      => 'in:none,local,cloud',
            'audio_options'       => 'in:both,telephony,voip',
            'host_video'          => 'in:enable,disable',
            'participant_video'   => 'in:enable,disable',
            'join_before_host'    => 'in:enable,disable',
            'waiting_room'        => 'in:enable,disable',
            'mute_upon_entry'     => 'in:enable,disable',
            'class_join_approval' => 'in:automatically,manually,no-registration',
        ]);

        // Don't overwrite client_secret if it's not being changed (masked value sent back)
        if (!$request->has('client_secret') || empty($request->client_secret) || str_contains($request->client_secret, '****')) {
            unset($validated['client_secret']);
        }

        $settings = ZoomSetting::getSettings();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zoom settings updated successfully',
            'data'    => $settings->fresh(),
        ]);
    }

    /**
     * Test Zoom credentials by attempting to get an access token.
     */
    public function testConnection()
    {
        $settings = ZoomSetting::getSettings();

        if (!$settings->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Zoom credentials not configured. Please set Account ID, Client ID, and Client Secret first.',
            ], 400);
        }

        try {
            $token = $this->getZoomAccessToken($settings);

            if ($token) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Zoom connection successful! Credentials are valid.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => '❌ Failed to authenticate with Zoom. Check your credentials.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Connection error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Zoom OAuth access token using Server-to-Server OAuth.
     */
    private function getZoomAccessToken(ZoomSetting $settings): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . urlencode($settings->account_id),
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . base64_encode($settings->client_id . ':' . $settings->client_secret),
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
}
